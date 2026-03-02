<?php

namespace App\Providers;

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
                'AUTH_KEY'       => $this->authKey,
                'message'        => $message,
                'senderId'       => $this->smsConfig['sender_id'],
                'routeId'        => $this->smsConfig['route_id'],
                'mobileNos'      => $mobile,
                'smsContentType' => $this->smsConfig['content_type'],
            ];

            // Uncomment when DLT template registration is in place:
            // if ($templateId) { $params['templateid'] = $templateId; }

            $response     = Http::timeout($this->smsConfig['timeout'])
                ->withHeaders(['Cache-Control' => 'no-cache'])
                ->get($endpoint, $params);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['responseCode'])) {
                $isSuccess = in_array($responseData['responseCode'], ['3001', '200']);

                if ($isSuccess) {
                    Log::info('[MsgClub] SMS sent', [
                        'mobile'    => $mobile,
                        'reference' => $responseData['response'] ?? $responseData['requestId'] ?? null,
                    ]);

                    return [
                        'success'   => true,
                        'reference' => $responseData['response'] ?? $responseData['requestId'] ?? null,
                        'response'  => $responseData,
                        'error'     => null,
                    ];
                }

                // API responded but rejected — log here so channels don't need to duplicate it
                $reason = $responseData['reason'] ?? 'SMS rejected by MsgClub';
                Log::error('[MsgClub] SMS API rejection', [
                    'mobile'        => $mobile,
                    'response_code' => $responseData['responseCode'],
                    'reason'        => $reason,
                ]);

                return [
                    'success'   => false,
                    'reference' => null,
                    'response'  => $responseData,
                    'error'     => $reason,
                ];
            }

            Log::error('[MsgClub] SMS invalid API response', [
                'mobile'      => $mobile,
                'http_status' => $response->status(),
                'body'        => $responseData,
            ]);

            return [
                'success'   => false,
                'reference' => null,
                'response'  => $responseData,
                'error'     => 'Invalid API response',
            ];

        } catch (Exception $e) {
            Log::error('[MsgClub] SMS network/exception error', [
                'mobile' => $mobile,
                'error'  => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'reference' => null,
                'response'  => [],
                'error'     => $e->getMessage(),
            ];
        }
    }

    /**
     * @param array $attachments Array of file paths
     */
    public function sendEmail(
        string $email,
        string $name,
        string $subject,
        string $htmlContent,
        array  $attachments = [],
    ): array {
        try {
            $endpoint = "{$this->baseUrl}/sendEmail/email";

            $payload = [
                'routeId'     => $this->emailConfig['route_id'],
                'contentType' => 'html',
                'mailContent' => $htmlContent,
                'subject'     => $subject,
                'fromEmail'   => $this->emailConfig['from_email'],
                'fromName'    => $this->emailConfig['from_name'],
                'displayName' => $this->emailConfig['display_name'] ?? $this->emailConfig['from_name'],
                'toEmailSet'  => [
                    ['email' => $email, 'personName' => $name],
                ],
            ];

            if (!empty($attachments)) {
                $payload['attachmentType'] = '1'; // 1 = base64
                $payload['attachments']    = $this->prepareAttachments($attachments);
            }

            $response     = Http::timeout($this->emailConfig['timeout'])
                ->withHeaders([
                    'Content-Type'  => 'application/json',
                    'Cache-Control' => 'no-cache',
                ])
                ->post($endpoint . "?AUTH_KEY={$this->authKey}", $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['responseCode'])) {
                if (in_array($responseData['responseCode'], ['3001', '200'])) {
                    $reference = $responseData['jobId'] ?? $responseData['requestId'] ?? null;

                    Log::info('[MsgClub] Email sent', [
                        'to'        => $email,
                        'subject'   => $subject,
                        'reference' => $reference,
                    ]);

                    return [
                        'success'   => true,
                        'reference' => $reference,
                        'response'  => $responseData,
                        'error'     => null,
                    ];
                }

                $reason = $responseData['reason'] ?? 'Email rejected by MsgClub';
                Log::error('[MsgClub] Email API rejection', [
                    'to'            => $email,
                    'subject'       => $subject,
                    'response_code' => $responseData['responseCode'],
                    'reason'        => $reason,
                ]);

                return [
                    'success'   => false,
                    'reference' => null,
                    'response'  => $responseData,
                    'error'     => $reason,
                ];
            }

            Log::error('[MsgClub] Email invalid API response', [
                'to'          => $email,
                'http_status' => $response->status(),
                'body'        => $responseData,
            ]);

            return [
                'success'   => false,
                'reference' => null,
                'response'  => $responseData,
                'error'     => 'Invalid API response',
            ];

        } catch (Exception $e) {
            Log::error('[MsgClub] Email network/exception error', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'success'   => false,
                'reference' => null,
                'response'  => [],
                'error'     => $e->getMessage(),
            ];
        }
    }

    public function sendVoice(string $mobile, string $message): array
    {
        // TODO: Implement when MsgClub voice API credentials are available
        Log::warning('[MsgClub] Voice notification not yet implemented', [
            'mobile' => $mobile,
        ]);

        return [
            'success'   => false,
            'reference' => null,
            'response'  => [],
            'error'     => 'Voice notifications not yet implemented',
        ];
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
                $fileName    = basename($path);
                $mimeType    = $this->getMimeType($path);

                $attachments[] = [
                    'fileType' => $mimeType,
                    'fileName' => $fileName,
                    'fileData' => base64_encode($fileContent),
                ];
            } catch (Exception $e) {
                Log::error('[MsgClub] Failed to prepare attachment', [
                    'path'  => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $attachments;
    }

    private function getMimeType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            default => mime_content_type($path) ?: 'application/octet-stream',
        };
    }
}
