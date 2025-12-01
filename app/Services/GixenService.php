<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GixenService
{
    protected $username;
    protected $password;
    protected $apiUrl;

    public function __construct()
    {
        $this->username = config('gixen.username');
        $this->password = config('gixen.password');
        $this->apiUrl = config('gixen.api_url');
    }

    /**
     * Extract the numeric item ID from eBay's legacy format (e.g., "v1|306622440083|0" -> "306622440083")
     * 
     * @param string $itemId The item ID in any format
     * @return string The numeric item ID
     */
    private function extractItemId($itemId)
    {
        // If the item ID contains pipes, extract the middle part (e.g., "v1|306622440083|0")
        if (strpos($itemId, '|') !== false) {
            $parts = explode('|', $itemId);
            // Return the middle part (usually index 1)
            return $parts[1] ?? $itemId;
        }
        
        // If no pipes, return as-is (already numeric)
        return $itemId;
    }

    /**
     * Submit a bid to Gixen for an eBay item
     * 
     * @param string $itemId The eBay item ID (may be in legacy format like "v1|306622440083|0")
     * @param float $maxBid The maximum bid amount
     * @param int|null $snipeGroup Optional snipe group ID
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function submitBid($itemId, $maxBid, $snipeGroup = null)
    {
        if (!$this->username || !$this->password) {
            return [
                'success' => false,
                'message' => 'Gixen credentials not configured.',
                'data' => null
            ];
        }

        // Extract the numeric item ID from eBay's legacy format
        $cleanItemId = $this->extractItemId($itemId);

        $params = [
            'username' => $this->username,
            'password' => $this->password,
            'itemid' => $cleanItemId,
            'maxbid' => number_format((float)$maxBid, 2, '.', '')
        ];

        if ($snipeGroup !== null) {
            $params['snipegroup'] = $snipeGroup;
        }

        try {
            $response = Http::get($this->apiUrl, $params);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to Gixen API. HTTP Status: ' . $response->status(),
                    'data' => null
                ];
                
            }

            // Parse XML response
            $xml = simplexml_load_string($response->body());
            
            if ($xml === false) {
                Log::error('Gixen API: Failed to parse XML response', [
                    'response_body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid response from Gixen API.',
                    'data' => null
                ];
            }

            // Convert XML to array for easier handling
            $data = json_decode(json_encode($xml), true);

            // Check for error messages in the response
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'message' => 'Gixen error: ' . $data['error'],
                    'data' => $data
                ];
            }

            // Check for success indicators
            // The exact structure may vary, but typically success responses contain item information
            if (isset($data['item']) || isset($data['success']) || isset($data['itemid'])) {
                return [
                    'success' => true,
                    'message' => 'Bid submitted successfully to Gixen.',
                    'data' => $data
                ];
            }

            // If we get here, the response structure is unexpected
            Log::warning('Gixen API: Unexpected response structure', [
                'response_data' => $data
            ]);

            return [
                'success' => true, // Assume success if no error is present
                'message' => 'Bid submitted to Gixen.',
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Gixen API: Exception occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error submitting bid to Gixen: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Get the snipelist from main Gixen server
     * 
     * @param bool $includeComments Whether to include comments in the response
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function getSnipelistMain($includeComments = false)
    {
        if (!$this->username || !$this->password) {
            return [
                'success' => false,
                'message' => 'Gixen credentials not configured.',
                'data' => null
            ];
        }

        $params = [
            'username' => $this->username,
            'password' => $this->password,
            'listsnipesmain' => 1
        ];

        if ($includeComments) {
            $params['includecomments'] = 1;
        }

        try {
            $response = Http::get($this->apiUrl, $params);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to Gixen API. HTTP Status: ' . $response->status(),
                    'data' => null
                ];
            }

            // Parse XML response
            $xml = simplexml_load_string($response->body());
            
            if ($xml === false) {
                Log::error('Gixen API: Failed to parse XML response', [
                    'response_body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid response from Gixen API.',
                    'data' => null
                ];
            }

            // Convert XML to array for easier handling
            $data = json_decode(json_encode($xml), true);

            // Check for error messages
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'message' => 'Gixen error: ' . $data['error'],
                    'data' => $data
                ];
            }

            return [
                'success' => true,
                'message' => 'Snipelist retrieved successfully.',
                'data' => $data
            ];

        } catch (\Exception $e) {
            Log::error('Gixen API: Exception occurred', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error retrieving snipelist: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}

