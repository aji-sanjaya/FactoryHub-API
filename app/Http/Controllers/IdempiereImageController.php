<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\IdempiereService;
use Illuminate\Support\Facades\Log;

class IdempiereImageController extends Controller
{
    protected $idempiereService;

    public function __construct(IdempiereService $idempiereService)
    {
        $this->idempiereService = $idempiereService;
    }

    /**
     * Display image from iDempiere by AD_User_ID
     * The image data is nested in AD_Image_ID.data field
     */
    public function show($userId)
    {
        try {
            Log::info("Fetching image for user ID: {$userId}");

            // Call iDempiere API to get user data with AD_Image_ID
            $url = "models/AD_User/{$userId}";
            $response = $this->idempiereService->get($url);

            if ($response->successful()) {
                $userData = $response->json();
                Log::info("User data retrieved", ['has_image' => isset($userData['AD_Image_ID'])]);

                // Check if AD_Image_ID exists and has data
                if (isset($userData['AD_Image_ID']['data']) && !empty($userData['AD_Image_ID']['data'])) {
                    $base64Data = $userData['AD_Image_ID']['data'];

                    // Decode base64 to binary
                    $imageContent = base64_decode($base64Data);

                    if ($imageContent === false) {
                        Log::error("Failed to decode base64 image for user {$userId}");
                        return redirect('/images/user/default.png');
                    }

                    // Detect content type from binary data
                    $contentType = 'image/jpeg'; // Default
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $detectedType = $finfo->buffer($imageContent);
                    if ($detectedType) {
                        $contentType = $detectedType;
                    }

                    Log::info("Image found and decoded successfully", ['content_type' => $contentType]);

                    return response($imageContent)
                        ->header('Content-Type', $contentType)
                        ->header('Cache-Control', 'public, max-age=31536000'); // Cache for 1 year
                } else {
                    Log::warning("No image data found in AD_Image_ID for user {$userId}");
                }
            } else {
                Log::error("API request failed for user {$userId}", ['status' => $response->status()]);
            }

            // If image not found or error, return default image
            return redirect('/images/user/default.png');

        } catch (\Exception $e) {
            Log::error("Error fetching image for user {$userId}: " . $e->getMessage());
            return redirect('/images/user/default.png');
        }
    }
}
