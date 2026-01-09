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
     *
     * @param string $mobile Mobile number with country code
     * @param string $message SMS message content
     * @param string|null $templateId Template ID registered with provider
     * @return array ['success' => bool, 'reference' => string|null, 'response' => array, 'error' => string|null]
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
            Log::info("SMS endpoint: ". $endpoint);

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
     * Send Email via MsgClub API
     *
     * @param string $email Recipient email address
     * @param string $name Recipient name
     * @param string $subject Email subject
     * @param string $htmlContent Email HTML content
     * @return array ['success' => bool, 'reference' => string|null, 'response' => array, 'error' => string|null]
     */
    public function sendEmail(
        string $email,
        string $name,
        string $subject,
        string $htmlContent,
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
                "displayName" => $this->emailConfig["display_name"],
                "toEmailSet" => [
                    [
                        "email" => $email,
                        "personName" => $name,
                    ],
                ],
            ];

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
     * Send Voice call via MsgClub API
     * TODO: Implement when voice API details are available
     *
     * @param string $mobile Mobile number with country code
     * @param string $message Voice message content
     * @return array ['success' => bool, 'reference' => string|null, 'response' => array, 'error' => string|null]
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
