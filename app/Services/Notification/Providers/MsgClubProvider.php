<?php

namespace App\Services\Notification\Providers;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MsgClubProvider
{
    private string $authKey;
    private string $baseUrl;
    private array $smsConfig;
    private array $emailConfig;

    public function __construct()
    {
        $this->authKey = config("notifications.msgclub.auth_key");
        $this->baseUrl = config("notifications.msgclub.base_url");
        $this->smsConfig = config("notifications.msgclub.sms");
        $this->emailConfig = config("notifications.msgclub.email");
    }

    /**
     * Send SMS via MsgClub API
     */
    public function sendSms(
        string $mobile,
        string $message,
        ?string $templateId = null,
    ): array {
        try {
            $endpoint = "$this->baseUrl/sendSMS/sendGroupSms";

            $params = [
                "AUTH_KEY" => $this->authKey,
                "message" => $message,
                "senderId" => $this->smsConfig["sender_id"],
                "routeId" => $this->smsConfig["route_id"],
                "mobileNos" => $mobile,
                "smsContentType" => $this->smsConfig["content_type"],
            ];

//            if ($templateId) {
//                $params["templateid"] = $templateId;
//            }

            Log::info("SMS Message: " . $message);
            Log::info("SMS params: ", $params);

            $response = Http::timeout($this->smsConfig["timeout"])
                ->withHeaders(["Cache-Control" => "no-cache"])
                ->get($endpoint, $params);

            $responseData = $response->json();

            // Check if the API call was successful
            if (
                $response->successful() &&
                isset($responseData["responseCode"])
            ) {
                $isSuccess = in_array($responseData["responseCode"], [
                    "3001",
                    "200",
                ]);

                if ($isSuccess) {
                    return [
                        "success" => true,
                        "reference" =>
                            $responseData["response"] ??
                            ($responseData["requestId"] ?? null),
                        "response" => $responseData,
                        "error" => null,
                    ];
                }

                return [
                    "success" => false,
                    "reference" => null,
                    "response" => $responseData,
                    "error" => $responseData["reason"] ?? "SMS sending failed",
                ];
            }

            return [
                "success" => false,
                "reference" => null,
                "response" => $responseData,
                "error" => "Invalid API response",
            ];
        } catch (Exception $e) {
            Log::error("MsgClub SMS API error", [
                "mobile" => $mobile,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "reference" => null,
                "response" => [],
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Send Email via MsgClub API with optional attachments
     *
     * @param string $email Recipient email address
     * @param string $name Recipient name
     * @param string $subject Email subject
     * @param string $htmlContent Email HTML content
     * @param array $attachments Array of attachment file paths
     * @return array ['success' => bool, 'reference' => string|null, 'response' => array, 'error' => string|null]
     */
    public function sendEmail(
        string $email,
        string $name,
        string $subject,
        string $htmlContent,
        array $attachments = []
    ): array {
        try {
            $endpoint = "$this->baseUrl/sendEmail/email";

            $payload = [
                "routeId" => $this->emailConfig["route_id"],
                "contentType" => "html",
                "mailContent" => $htmlContent,
                "subject" => $subject,
                "fromEmail" => $this->emailConfig["from_email"],
                "fromName" => $this->emailConfig["from_name"],
                "displayName" => $this->emailConfig["display_name"] ?? $this->emailConfig["from_name"],
                "toEmailSet" => [
                    [
                        "email" => $email,
                        "personName" => $name,
                    ],
                ],
            ];

            // Add attachments if provided
            if (!empty($attachments)) {
                $payload["attachmentType"] = "1"; // 1 for base64
                $payload["attachments"] = $this->prepareAttachments($attachments);
            }

            Log::info("Sending email via MsgClub", [
                'to' => $email,
                'subject' => $subject,
                'has_attachments' => !empty($attachments),
                'attachment_count' => count($attachments),
            ]);

            $response = Http::timeout($this->emailConfig["timeout"])
                ->withHeaders([
                    "Content-Type" => "application/json",
                    "Cache-Control" => "no-cache",
                ])
                ->post($endpoint . "?AUTH_KEY={$this->authKey}", $payload);

            $responseData = $response->json();

            // Check if the API call was successful
            if (
                $response->successful() &&
                isset($responseData["responseCode"])
            ) {
                if (in_array($responseData["responseCode"], ["3001", "200"])) {
                    return [
                        "success" => true,
                        "reference" =>
                            $responseData["jobId"] ??
                            ($responseData["requestId"] ?? null),
                        "response" => $responseData,
                        "error" => null,
                    ];
                }

                return [
                    "success" => false,
                    "reference" => null,
                    "response" => $responseData,
                    "error" =>
                        $responseData["reason"] ?? "Email sending failed",
                ];
            }

            return [
                "success" => false,
                "reference" => null,
                "response" => $responseData,
                "error" => "Invalid API response",
            ];
        } catch (Exception $e) {
            Log::error("MsgClub Email API error", [
                "email" => $email,
                "error" => $e->getMessage(),
            ]);

            return [
                "success" => false,
                "reference" => null,
                "response" => [],
                "error" => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare attachments for MsgClub API
     * Convert file paths to base64 encoded data
     *
     * @param array $attachmentPaths Array of file paths
     * @return array Array of attachment objects for API
     */
    private function prepareAttachments(array $attachmentPaths): array
    {
        $attachments = [];

        foreach ($attachmentPaths as $path) {
            if (!file_exists($path)) {
                Log::warning("Attachment file not found", ['path' => $path]);
                continue;
            }

            try {
                $fileContent = file_get_contents($path);
                $base64Content = base64_encode($fileContent);
                $fileName = basename($path);
                // $mimeType = $this->getMimeType($path);
                $mimeType = "application/pdf";

                $attachments[] = [
                    'fileType' => $mimeType,
                    'fileName' => $fileName,
                    'fileData' => $base64Content,
                ];

                Log::info("Attachment prepared", [
                    'file' => $fileName,
                    'mime' => $mimeType,
                    'size' => strlen($fileContent),
                ]);
            } catch (Exception $e) {
                Log::error("Failed to prepare attachment", [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $attachments;
    }

    /**
     * Get MIME type for file
     */
//    private function getMimeType(string $path): string
//    {
//        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
//
//        return match ($extension) {
//            'pdf' => 'application/pdf',
//            'txt' => 'text/plain',
//            'csv' => 'text/csv',
//            'doc' => 'application/msword',
//            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
//            'xls' => 'application/vnd.ms-excel',
//            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
//            'jpg', 'jpeg' => 'image/jpeg',
//            'png' => 'image/png',
//            'gif' => 'image/gif',
//            default => mime_content_type($path) ?: 'application/octet-stream',
//        };
//    }

    /**
     * Send Voice call via MsgClub API
     */
    public function sendVoice(string $mobile, string $message): array
    {
        // TODO: Implement voice API call when API details are available
        Log::warning("Voice notification attempted but not yet implemented", [
            "mobile" => $mobile,
        ]);

        return [
            "success" => false,
            "reference" => null,
            "response" => [],
            "error" => "Voice notifications not yet implemented",
        ];
    }
}
