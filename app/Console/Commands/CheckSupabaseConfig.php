<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckSupabaseConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:supabase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica se as configurações do Supabase estão corretas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando configurações do Supabase...');
        
        $url = config('services.supabase.url');
        $key = config('services.supabase.key');
        $bucket = config('services.supabase.bucket');
        
        $this->info('URL: ' . ($url ?: 'Não configurada'));
        $this->info('Key: ' . ($key ? (substr($key, 0, 5) . '...' . substr($key, -5)) : 'Não configurada'));
        $this->info('Bucket: ' . ($bucket ?: 'Não configurado'));
        
        if (empty($url) || empty($key) || empty($bucket)) {
            $this->error('As configurações do Supabase estão incompletas!');
            $this->info('Execute o comando php artisan setup:supabase para configurar.');
            return 1;
        }
        
        // Testar conexão com o Supabase
        $this->info('Testando conexão com o Supabase...');
        
        try {
            // Verificar autenticação usando o endpoint de lista de buckets
            $ch = curl_init($url . '/storage/v1/bucket');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $key
            ]);
            
            $result = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $buckets = json_decode($result, true);
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
                    $this->info('Conexão estabelecida com sucesso!');
                    $this->info("Bucket '{$bucket}' foi encontrado no Supabase.");
                    
                    // Verificar se o bucket é público
                    $this->info('Verificando permissões do bucket...');
                    
                    $ch = curl_init($url . '/storage/v1/bucket/' . $bucket);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $key
                    ]);
                    
                    $bucketInfo = curl_exec($ch);
                    curl_close($ch);
                    
                    $bucketData = json_decode($bucketInfo, true);
                    
                    if (isset($bucketData['public']) && $bucketData['public'] === true) {
                        $this->info("Bucket '{$bucket}' está configurado como público.");
                    } else {
                        $this->warn("Bucket '{$bucket}' não está configurado como público.");
                        $this->info("Para uploads funcionarem corretamente, configure o bucket como público no painel do Supabase.");
                    }
                    
                    return 0;
                } else {
                    $this->warn("Conexão estabelecida, mas o bucket '{$bucket}' não foi encontrado.");
                    
                    if (is_array($buckets)) {
                        $this->info("Buckets disponíveis: " . (count($buckets) > 0 ? implode(', ', array_column($buckets, 'name')) : 'nenhum'));
                    }
                    
                    $this->info("Para criar o bucket '{$bucket}':");
                    $this->info("1. Acesse o painel do Supabase (https://app.supabase.com)");
                    $this->info("2. Selecione seu projeto");
                    $this->info("3. Vá para Storage > Buckets");
                    $this->info("4. Clique em 'Create new bucket'");
                    $this->info("5. Digite o nome '{$bucket}'");
                    $this->info("6. Marque a opção 'Public bucket' para tornar o bucket público");
                    $this->info("7. Clique em 'Create bucket'");
                    $this->info("8. Depois de criar, vá para a aba 'Policies' do bucket");
                    $this->info("9. Verifique se existem políticas que permitam upload e download (ou crie-as)");
                    
                    return 1;
                }
            } else {
                $this->error('Erro ao conectar com o Supabase. Código de status: ' . $statusCode);
                $this->info('Resposta: ' . $result);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Erro ao conectar com o Supabase: ' . $e->getMessage());
            return 1;
        }
    }
} 