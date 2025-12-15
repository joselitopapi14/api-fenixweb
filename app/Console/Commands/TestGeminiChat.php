<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiService;
use App\Models\User;

class TestGeminiChat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:gemini-chat {user_id} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Gemini chat functionality with a specific user and message';

    public function __construct(private GeminiService $geminiService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $message = $this->argument('message');

        try {
            $user = User::findOrFail($userId);
            $this->info("Testing Gemini chat for user: {$user->name}");
            $this->info("Message: {$message}");
            $this->info("---");

            $response = $this->geminiService->sendMessage($message, $user);

            if ($response['success']) {
                $this->info("✅ Success!");
                $this->info("Response: " . $response['message']);
            } else {
                $this->error("❌ Error: " . $response['error']);
            }

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
