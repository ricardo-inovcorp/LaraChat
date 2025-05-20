<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupSupabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:supabase {url?} {key?} {bucket=avatars}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura as credenciais do Supabase para armazenamento de arquivos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url') ?: $this->ask('Qual é a URL do seu projeto Supabase? (ex: https://your-project-id.supabase.co)');
        $key = $this->argument('key') ?: $this->secret('Qual é a chave anônima (anon key) do seu projeto Supabase?');
        $bucket = $this->argument('bucket');

        $this->info('Configurando credenciais do Supabase...');

        // Verifica se o arquivo .env existe
        if (!File::exists(base_path('.env'))) {
            $this->error('Arquivo .env não encontrado!');
            return 1;
        }

        $envContent = File::get(base_path('.env'));

        // Verifica se as variáveis já existem no arquivo .env
        if (strpos($envContent, 'SUPABASE_URL') !== false) {
            // Atualizando variáveis existentes
            $envContent = preg_replace('/SUPABASE_URL=.*/', 'SUPABASE_URL=' . $url, $envContent);
            $envContent = preg_replace('/SUPABASE_KEY=.*/', 'SUPABASE_KEY=' . $key, $envContent);
            $envContent = preg_replace('/SUPABASE_BUCKET=.*/', 'SUPABASE_BUCKET=' . $bucket, $envContent);
        } else {
            // Adicionar novas variáveis
            $envContent .= "\n\n# Supabase Storage Configuration\n";
            $envContent .= "SUPABASE_URL={$url}\n";
            $envContent .= "SUPABASE_KEY={$key}\n";
            $envContent .= "SUPABASE_BUCKET={$bucket}\n";
        }

        // Salvar as alterações no arquivo .env
        File::put(base_path('.env'), $envContent);
        
        // Criar o bucket no Supabase se não existir
        if ($this->confirm('Deseja criar o bucket "' . $bucket . '" automaticamente no Supabase?', true)) {
            $this->info('Tentando criar o bucket ' . $bucket . '...');
            
            try {
                $ch = curl_init($url . '/storage/v1/bucket');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $key,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'id' => $bucket,
                    'name' => $bucket,
                    'public' => true
                ]));
                
                $result = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($statusCode >= 200 && $statusCode < 300) {
                    $this->info('Bucket criado com sucesso!');
                    
                    // Configurar políticas de acesso público para o bucket
                    $this->info('Configurando políticas de acesso...');
                    
                    $ch = curl_init($url . '/storage/v1/bucket/' . $bucket . '/policy');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $key,
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'name' => 'public',
                        'definition' => [
                            'type' => 'INSERT',
                            'resources' => ['objects'],
                            'action' => 'INSERT',
                            'role' => 'authenticated',
                            'fields' => null
                        ]
                    ]));
                    
                    $result = curl_exec($ch);
                    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($statusCode >= 200 && $statusCode < 300) {
                        $this->info('Política de upload configurada com sucesso!');
                    } else {
                        $this->warn('Não foi possível configurar a política de upload. Você precisará fazer isso manualmente no painel do Supabase.');
                    }
                    
                    // Política para download público
                    $ch = curl_init($url . '/storage/v1/bucket/' . $bucket . '/policy');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $key,
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'name' => 'public_download',
                        'definition' => [
                            'type' => 'SELECT',
                            'resources' => ['objects'],
                            'action' => 'SELECT',
                            'role' => 'anon',
                            'fields' => null
                        ]
                    ]));
                    
                    $result = curl_exec($ch);
                    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($statusCode >= 200 && $statusCode < 300) {
                        $this->info('Política de download público configurada com sucesso!');
                    } else {
                        $this->warn('Não foi possível configurar a política de download público. Você precisará fazer isso manualmente no painel do Supabase.');
                    }
                    
                } else if ($statusCode === 409) {
                    $this->info('O bucket já existe! Não foi necessário criar novamente.');
                } else {
                    $this->error('Erro ao criar o bucket. Código de status: ' . $statusCode);
                    $this->info('Resposta: ' . $result);
                    $this->info('Você precisará criar o bucket manualmente no painel do Supabase.');
                }
            } catch (\Exception $e) {
                $this->error('Erro ao criar o bucket: ' . $e->getMessage());
                $this->info('Você precisará criar o bucket manualmente no painel do Supabase.');
            }
        }

        $this->info('Configuração do Supabase concluída com sucesso!');
        $this->info('Execute php artisan config:clear para limpar o cache de configuração.');

        return 0;
    }
} 