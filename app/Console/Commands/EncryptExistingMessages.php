<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptExistingMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:encrypt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criptografa todas as mensagens existentes que ainda não foram criptografadas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando criptografia de mensagens existentes...');
        
        // Obter todas as mensagens do banco de dados
        $messages = DB::table('messages')->get();
        $totalMessages = $messages->count();
        $encryptedCount = 0;
        
        $this->output->progressStart($totalMessages);
        
        foreach ($messages as $message) {
            // Verificar se a mensagem já está criptografada
            try {
                // Tenta descriptografar - se funcionar, já está criptografada
                Crypt::decrypt($message->content);
                // Se não lançou exceção, já está criptografada
                $this->output->progressAdvance();
                continue;
            } catch (\Exception $e) {
                // Não está criptografada, vamos criptografar
                DB::table('messages')
                    ->where('id', $message->id)
                    ->update(['content' => Crypt::encrypt($message->content)]);
                
                $encryptedCount++;
                $this->output->progressAdvance();
            }
        }
        
        $this->output->progressFinish();
        $this->info("Criptografia concluída! $encryptedCount de $totalMessages mensagens foram criptografadas.");
        
        return Command::SUCCESS;
    }
} 