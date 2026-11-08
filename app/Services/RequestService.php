<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\Medicine;
use App\Models\MedicineRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestService
{
    public function __construct(private MatchingService $matching) {}

    public function create(User $owner, array $data): MedicineRequest
    {
        $request = new MedicineRequest($this->withCatalogueDetails($data));
        $request->user_id = $owner->id;
        $request->status = RequestStatus::Active;
        $request->expires_at = now()->addDays((int) Setting::get('request_expiry_days'));
        $request->save();

        $this->matching->suggestIdentity($request); // free-text requests only; a no-op when AI is off
        $this->matching->matchRequest($request);

        return $request->refresh();
    }

    public function update(MedicineRequest $request, array $data): MedicineRequest
    {
        if (! $request->status->isOpen()) {
            throw ValidationException::withMessages(['status' => 'Closed requests cannot be edited.']);
        }

        DB::transaction(function () use ($request, $data) {
            $request->fill($this->withCatalogueDetails($data))->save();
            $this->matching->suggestIdentity($request);
            // Details changed, so previous matches may no longer apply.
            $request->matches()->delete();
            if ($request->status === RequestStatus::MatchFound) {
                $request->status = RequestStatus::Active;
                $request->save();
            }
        });
        $this->matching->matchRequest($request);

        return $request->refresh();
    }

    public function cancel(MedicineRequest $request): void
    {
        if (! $request->status->isOpen()) {
            throw ValidationException::withMessages(['status' => 'This request is already closed.']);
        }
        $request->status = RequestStatus::Cancelled;
        $request->save();
    }

    /** If a catalogue entry is chosen, its identity overrides free text so matching is exact. */
    private function withCatalogueDetails(array $data): array
    {
        if (! empty($data['medicine_id']) && $medicine = Medicine::find($data['medicine_id'])) {
            $data['medicine_name'] = $medicine->brand_name;
            $data['generic_name'] = $medicine->generic_name;
            $data['strength'] = $medicine->strength;
            $data['dosage_form'] = $medicine->dosage_form;
        }

        return $data;
    }
}
