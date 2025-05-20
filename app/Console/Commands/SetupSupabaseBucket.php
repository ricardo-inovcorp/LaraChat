<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupSupabaseBucket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:supabase-bucket';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guia interativo para configurar um bucket no Supabase';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Guia para configuração do bucket do Supabase');
        $this->info('===========================================');
        
        $url = config('services.supabase.url');
        $key = config('services.supabase.key');
        $bucket = config('services.supabase.bucket');
        
        if (empty($url) || empty($key)) {
            $this->error('As configurações básicas do Supabase (URL e KEY) estão ausentes!');
            $this->info('Adicione-as ao seu arquivo .env primeiro:');
            $this->info('SUPABASE_URL=sua_url_do_supabase');
            $this->info('SUPABASE_KEY=sua_chave_do_supabase');
            $this->info('SUPABASE_BUCKET=nome_do_bucket (padrão: avatars)');
            return 1;
        }
        
        $this->info('URL do Supabase: ' . $url);
        $this->info('Chave configurada: ' . substr($key, 0, 5) . '...' . substr($key, -5));
        $this->info('Bucket configurado: ' . $bucket);
        
        $this->info("\nVerificando se o bucket '{$bucket}' existe...");
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
            ])->get("{$url}/storage/v1/bucket");
            
            if (!$response->successful()) {
                $this->error('Erro ao conectar com o Supabase: ' . $response->status());
                $this->info('Resposta: ' . $response->body());
                return 1;
            }
            
            $buckets = $response->json();
            $bucketExists = false;
            
            if (is_array($buckets)) {
                foreach ($buckets as $bucketData) {
                    if (isset($bucketData['name']) && $bucketData['name'] === $bucket) {
                        $bucketExists = true;
                        break;
                    }
                }
            }
            
            if ($bucketExists) {
                $this->info("O bucket '{$bucket}' já existe!");
                
                if ($this->confirm('Deseja ver as instruções para configurar políticas de acesso?', true)) {
                    $this->showPolicyInstructions($bucket);
                }
            } else {
                $this->warn("O bucket '{$bucket}' não existe.");
                $this->info("Você precisa criá-lo manualmente através do dashboard do Supabase.");
                
                if ($this->confirm('Deseja ver instruções detalhadas?', true)) {
                    $this->showBucketCreationInstructions($bucket);
                }
            }
        } catch (\Exception $e) {
            $this->error('Erro ao conectar com o Supabase: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    protected function showBucketCreationInstructions(string $bucket)
    {
        $this->info("\nComo criar o bucket '{$bucket}':");
        $this->info("1. Acesse o painel do Supabase (https://app.supabase.com)");
        $this->info("2. Selecione seu projeto");
        $this->info("3. Vá para Storage > Buckets");
        $this->info("4. Clique em 'Create new bucket'");
        $this->info("5. Digite o nome '{$bucket}'");
        $this->info("6. Marque a opção 'Public bucket' para tornar o bucket público");
        $this->info("7. Clique em 'Create bucket'");
        
        if ($this->confirm("\nBucket criado? Deseja ver as instruções para configurar políticas de acesso?", true)) {
            $this->showPolicyInstructions($bucket);
        }
    }
    
    protected function showPolicyInstructions(string $bucket)
    {
        $this->info("\nComo configurar políticas de acesso:");
        $this->info("1. No painel do Supabase, vá para Storage > Buckets");
        $this->info("2. Selecione o bucket '{$bucket}'");
        $this->info("3. Vá para a aba 'Policies'");
        $this->info("4. Se não houver políticas ou quiser adicionar novas, clique em 'Add policies'");
        $this->info("\nPara permitir acesso de leitura público (GET requests):");
        $this->info("- Em 'Select template', escolha 'For SELECT (or GET) operations'");
        $this->info("- Em 'Who can access', escolha 'Everyone, including anonymous users'");
        $this->info("- Clique em 'Create Policy'");
        $this->info("\nPara permitir upload (INSERT requests):");
        $this->info("- Em 'Select template', escolha 'For INSERT (or UPLOAD) operations'");
        $this->info("- Em 'Who can access', escolha 'Authenticated users only' ou a opção mais apropriada");
        $this->info("- Clique em 'Create Policy'");
        $this->info("\nPara permitir atualização (UPDATE requests):");
        $this->info("- Em 'Select template', escolha 'For UPDATE operations'");
        $this->info("- Em 'Who can access', escolha 'Authenticated users only'");
        $this->info("- Clique em 'Create Policy'");
    }
} 