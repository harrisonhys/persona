<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function maintain(array $data, ?string $id = null): Campaign
    {
        return DB::transaction(function () use ($data, $id) {
            $metadata = isset($data['metadata_json'])
                ? json_decode($data['metadata_json'], true)
                : null;

            if ($id) {
                $campaign = Campaign::findOrFail($id);
                $updateData = [
                    'title' => $data['title'],
                ];
                if (isset($data['status'])) {
                    $updateData['status'] = $data['status'];
                }
                if ($metadata !== null) {
                    $updateData['metadata'] = $metadata;
                }

                $campaign->update($updateData);
            } else {
                $campaign = Campaign::create([
                    'title' => $data['title'],
                    'status' => $data['status'] ?? 'DRAFT',
                    'metadata' => $metadata ?? [],
                ]);
            }

            return $campaign;
        });
    }
}
