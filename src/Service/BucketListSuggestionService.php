<?php

// src/Service/BucketListSuggestionService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BucketListSuggestionService
{
    private HttpClientInterface $httpClient;
    private string $huggingFaceApiKey;

    public function __construct(HttpClientInterface $httpClient, string $huggingFaceApiKey)
    {
        $this->httpClient = $httpClient;
        $this->huggingFaceApiKey = $huggingFaceApiKey;
    }


        public function getSuggestions(string $interests): array
        {
            try {
                $response = $this->httpClient->request('POST', 'https://router.huggingface.co/hyperbolic/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->huggingFaceApiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => "Generate bucket list items for someone interested in $interests.",
                            ],
                        ],
                        'max_tokens' => 500,
                        'model' => 'deepseek-ai/DeepSeek-V3-0324',
                        'stream' => false,
                    ],
                ]);
    
                // Log the raw response for debugging
                $rawContent = $response->getContent(false); // Get raw response content
                error_log('Hugging Face API Raw Response: ' . $rawContent);
    
                $data = json_decode($rawContent, true); // Decode JSON manually
                error_log('Hugging Face API Decoded Response: ' . json_encode($data));
    
                // Check if the response contains the expected field
                if (isset($data['choices'][0]['message']['content']) && !empty($data['choices'][0]['message']['content'])) {
                    return explode("\n", $data['choices'][0]['message']['content']);
                }
    
                // Log unexpected responses
                error_log('Unexpected Hugging Face API Response: ' . json_encode($data));
                return ['No suggestions could be generated. Please try again later.'];
            } catch (\Exception $e) {
                // Log the error
                error_log('Hugging Face API Error: ' . $e->getMessage());
                return ['An error occurred while fetching suggestions.'];
            }
        }
    }