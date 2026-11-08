<?php

namespace App\Services;

use App\Enums\MedicineEligibility;
use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Models\Medicine;
use App\Models\MedicineRequest;
use App\Models\User;
use App\Models\VerificationSubmission;
use App\Notifications\VerificationStatusChanged;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Identity and prescription checks by authorised human reviewers.
 * Documents are stored on the private "local" disk and are never given a public URL.
 * AI is never used to approve a document.
 */
class VerificationService
{
    public const DISK = 'local';

    public function submit(User $user, string $kind, UploadedFile $file, ?MedicineRequest $request = null): VerificationSubmission
    {
        if ($request && $request->user_id !== $user->id) {
            throw ValidationException::withMessages(['request_id' => 'Choose one of your own requests.']);
        }
        if ($kind === 'prescription' && ! $request) {
            throw ValidationException::withMessages(['request_id' => 'Choose the request this prescription is for.']);
        }

        return DB::transaction(function () use ($user, $kind, $file, $request) {
            $path = $file->storeAs("verification/{$user->id}", Str::uuid().'.'.$file->extension(), self::DISK);

            $submission = new VerificationSubmission([
                'kind' => $kind,
                'medicine_request_id' => $request?->id,
                'document_path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 150),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]);
            $submission->user_id = $user->id;
            $submission->status = VerificationStatus::Submitted;
            $submission->save();
            $submission->reviews()->create(['to_status' => VerificationStatus::Submitted, 'from_status' => null]);

            if ($request?->status->isOpen()) {
                $request->forceFill(['status' => RequestStatus::VerificationPending])->save();
            }

            return $submission;
        });
    }

    /** The first time a reviewer opens a submission it moves to "under review". */
    public function startReview(VerificationSubmission $submission, User $reviewer): void
    {
        if ($submission->status === VerificationStatus::Submitted) {
            $this->move($submission, VerificationStatus::UnderReview, $reviewer, null);
            AuditLogger::log('verification.review_started', "Verification #{$submission->id}", $submission);
        }
    }

    public function decide(VerificationSubmission $submission, User $reviewer, VerificationStatus $decision, string $note): void
    {
        if (! $submission->status->isPending()) {
            throw ValidationException::withMessages(['decision' => 'This submission has already been decided.']);
        }
        if (! in_array($decision, [VerificationStatus::Verified, VerificationStatus::Rejected, VerificationStatus::ReuploadRequired], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose verified, rejected or re-upload required.']);
        }
        if ($submission->user_id === $reviewer->id) {
            throw ValidationException::withMessages(['decision' => 'You cannot review your own submission.']);
        }

        DB::transaction(function () use ($submission, $reviewer, $decision, $note) {
            $this->move($submission, $decision, $reviewer, $note);
            $submission->forceFill(['reviewer_note' => $note, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()])->save();

            if ($decision === VerificationStatus::Verified && $submission->kind === 'identity') {
                $submission->user->forceFill(['identity_verified_at' => now()])->save();
            }
            // The request leaves "verification pending" once there is a final decision.
            $request = $submission->request;
            if ($request && $request->status === RequestStatus::VerificationPending && $decision !== VerificationStatus::ReuploadRequired) {
                $request->forceFill(['status' => $request->matches()->exists() ? RequestStatus::MatchFound : RequestStatus::Active])->save();
            }

            AuditLogger::log('verification.'.$decision->value, "Verification #{$submission->id} ({$submission->kind_label}) for {$submission->user->name}", $submission, $note);
        });

        $submission->user->notify(new VerificationStatusChanged($submission->refresh()));
    }

    /**
     * Has this member passed the check needed to receive this medicine?
     * Medicines that don't require verification are always cleared.
     */
    public function requesterCleared(User $user, Medicine $medicine): bool
    {
        if ($medicine->eligibility !== MedicineEligibility::VerificationRequired) {
            return true;
        }

        return VerificationSubmission::where('user_id', $user->id)
            ->where('kind', 'prescription')
            ->where('status', VerificationStatus::Verified)
            ->whereHas('request', fn ($q) => $q->where('medicine_id', $medicine->id)->orWhere('generic_name', $medicine->generic_name))
            ->exists();
    }

    private function move(VerificationSubmission $submission, VerificationStatus $to, User $reviewer, ?string $note): void
    {
        $submission->reviews()->create(['reviewer_id' => $reviewer->id, 'from_status' => $submission->status, 'to_status' => $to, 'note' => $note]);
        $submission->status = $to;
        $submission->save();
    }
}
