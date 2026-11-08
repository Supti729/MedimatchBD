<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationSubmission;

/**
 * Sensitive documents: the owner sees only status and notes; only authorised
 * reviewers (reviewer or admin role) can open the file. Donors never can.
 */
class VerificationSubmissionPolicy
{
    public function viewDocument(User $user, VerificationSubmission $submission): bool
    {
        return $user->isReviewer() && $user->id !== $submission->user_id;
    }

    public function decide(User $user, VerificationSubmission $submission): bool
    {
        return $user->isReviewer() && $user->id !== $submission->user_id && $submission->status->isPending();
    }
}
