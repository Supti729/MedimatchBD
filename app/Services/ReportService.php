<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\ListingStatus;
use App\Enums\ReportStatus;
use App\Models\Conversation;
use App\Models\MedicineListing;
use App\Models\Report;
use App\Models\User;
use App\Notifications\ReportStatusUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public function create(User $reporter, Model $target, string $category, ?string $details): Report
    {
        $reportedUser = match (true) {
            $target instanceof MedicineListing => $target->owner,
            $target instanceof Conversation => $target->otherParticipant($reporter),
            $target instanceof User => $target,
        };
        if ($reportedUser->id === $reporter->id) {
            throw ValidationException::withMessages(['category' => 'You cannot report yourself.']);
        }

        $report = new Report(['category' => $category, 'details' => $details]);
        $report->reporter_id = $reporter->id;
        $report->reported_user_id = $reportedUser->id;
        $report->status = ReportStatus::Open;
        $report->reportable()->associate($target);
        $report->save();

        return $report;
    }

    /** decision: resolved | rejected | escalated | suspend | block_listing */
    public function decide(Report $report, User $moderator, string $decision, string $note): void
    {
        if (! $report->status->isOpen()) {
            throw ValidationException::withMessages(['decision' => 'This report is already closed.']);
        }

        DB::transaction(function () use ($report, $moderator, $decision, $note) {
            if ($decision === 'suspend') {
                $user = $report->reportedUser;
                if (! $user || $user->isStaff()) {
                    throw ValidationException::withMessages(['decision' => 'This account cannot be suspended from a report.']);
                }
                app(UserModerationService::class)->changeStatus($user, AccountStatus::Suspended, "From report #{$report->id}: {$note}");
            }
            if ($decision === 'block_listing') {
                $listing = $report->listing;
                if (! $listing) {
                    throw ValidationException::withMessages(['decision' => 'There is no listing attached to this report.']);
                }
                if ($listing->status !== ListingStatus::Blocked) {
                    app(ListingService::class)->moderate($listing, 'block', "From report #{$report->id}: {$note}");
                }
            }

            $status = match ($decision) {
                'rejected' => ReportStatus::Rejected,
                'escalated' => ReportStatus::Escalated,
                default => ReportStatus::Resolved,
            };
            $report->status = $status;
            $report->handled_by = $moderator->id;
            $report->resolution_note = $note;
            $report->resolved_at = $status === ReportStatus::Escalated ? null : now();
            $report->save();

            AuditLogger::log("report.{$decision}", "Report #{$report->id} ({$report->category})", $report, $note);
        });

        $report->reporter->notify(new ReportStatusUpdated($report));
    }
}
