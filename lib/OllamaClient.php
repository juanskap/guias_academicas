<?php

class OllamaClient {
    private $baseUrl = 'http://localhost:11434';
    private $model = 'qwen2.5-coder:7b';

    public function __construct($model = null) {
        if ($model) $this->model = $model;
    }

    public function generate($prompt, $systemPrompt = '') {
        $fullPrompt = $systemPrompt ? "$systemPrompt\n\n$prompt" : $prompt;

        $ch = curl_init("$this->baseUrl/api/generate");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => $this->model,
            'prompt' => $fullPrompt,
            'stream' => false,
            'temperature' => 0.7,
            'options' => [
                'num_predict' => 2048,
            ]
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Ollama error: $error");
        }

        $data = json_decode($response, true);
        if (!$data || isset($data['error'])) {
            throw new Exception("Ollama: " . ($data['error'] ?? 'unknown error'));
        }

        return trim($data['response']);
    }
}
