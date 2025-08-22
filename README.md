# PDF Union — Interface web (PHP)

Este projeto adiciona uma interface web mínima em PHP ao script existente `pdf-union.php`.

Arquivos adicionados:
- `index.php` — interface de upload com ordenação no cliente (arrastar/soltar).
- `process.php` — recebe os uploads na ordem definida pelo usuário, salva em `arquivos/` com prefixos numéricos (`1-nome.ext`, `2-nome.ext`, ...), executa o processo de união e apresenta o link de download.
- `download.php` — faz o streaming do PDF gerado, remove o resultado após o envio e limpa os arquivos enviados com prefixos.

Como usar:
1. Coloque o projeto em um servidor com PHP (o ideal é usar o mesmo binário PHP que será usado para o `pdf-union.php`).
2. Abra `index.php` no navegador.
3. Envie os arquivos, reordene na interface conforme necessário e clique em "Gerar PDF Unificado".
4. Após o processamento, clique no link de download; ao baixar, o PDF gerado e os arquivos prefixados serão removidos do servidor.

Cobertura de requisitos:
- Tela de upload para PDF/imagens: Concluído (`index.php`).
- Ordenação visual e persistência da sequência como prefixos de nome: Concluído (`index.php` + `process.php`).
- Execução do `pdf-union.php` e apresentação do download: Concluído (`process.php` + `download.php`).
- Remoção/limpeza após download: Concluído (`download.php`).

Observações:
- A interface é propositalmente minimalista; você pode aprimorá-la com bibliotecas de arrastar/soltar ou melhorias visuais.
- O `process.php` utiliza `PHP_BINARY` para executar o mesmo binário PHP em modo CLI; verifique se o ambiente permite a função `exec()` e se o PHP possui permissões de leitura/escrita necessárias.

## Ambiente de desenvolvimento (WSL)

Passos rápidos para rodar e testar o projeto no WSL (Ubuntu recomendado):

1) Pré-requisitos

- WSL2 com uma distribuição Linux (ex: Ubuntu).
- PHP CLI (7.1+; recomendamos 7.4 ou 8.x para compatibilidade com `setasign/fpdi`).
- Composer (opcional se já houver `vendor/`, mas recomendado para manter dependências atualizadas).

2) Instalação de dependências no WSL

Abra um terminal WSL e execute:

```bash
sudo apt update
sudo apt install -y php php-cli php-zip php-mbstring unzip curl
# instalar o composer (se ainda não tiver)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Se optar por (re)instalar dependências do projeto:

```bash
# PDF Union — Front-end em PHP

Este projeto adiciona uma interface web mínima (em PHP) ao script original `pdf-union.php`, permitindo enviar arquivos (PDF e imagens), ordenar a sequência visualmente no navegador, gerar um PDF unificado e baixar o resultado.

Resumo dos artefatos adicionados
- `index.php` — interface web para upload, ordenação (arrastar/soltar) e envio para geração.
- `process.php` — recebe os arquivos enviados na ordem escolhida, salva em `arquivos/` com prefixos numéricos (`1-nome.pdf`, `2-nome.jpg`, ...), aciona o processo de união e apresenta o link de download.
- `download.php` — faz o streaming do PDF final ao cliente e remove o arquivo gerado e os uploads prefixados após o download.
- `cleanup.php` — (opcional) limpa os diretórios `arquivos/` e `resultado/` e redireciona para a página inicial.

Checklist de requisitos (implementação atual)
- Aceitar upload de PDF e imagens: Done (`index.php`).
- Permitir ordenação visual no front-end e persistir essa ordem nos nomes de arquivo salvos: Done (`index.php` + `process.php`).
- Executar o processo de união (`pdf-union.php`) e disponibilizar o download: Done (`process.php` + `download.php`).
- Remover arquivos gerados e uploads prefixados após o download: Done (`download.php`).

Requerimentos mínimos recomendados
- PHP CLI 7.4+ (8.x recomendado).
- Extensões PHP úteis: `gd` (para manipular imagens), `mbstring`, `zip`.
- Composer para instalar dependências (`setasign/fpdi`, `setasign/fpdf`).

Estrutura de diretórios usados

- `arquivos/` — arquivos enviados pelo usuário, salvos com prefixos numéricos antes de processar.
- `resultado/` — PDF(s) gerado(s) pela aplicação (nome padrão: `final_unificado_<timestamp>.pdf`).
- `vendor/` — dependências instaladas pelo Composer (FPDI/FPDF).

Instalação e execução rápida (WSL / Linux / macOS)

1) Instale PHP e Composer (exemplo Ubuntu/WSL):

```bash
sudo apt update
sudo apt install -y php php-cli php-zip php-mbstring php-gd unzip curl
# instalar o composer (se ainda não tiver)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

2) Instale dependências do projeto (se necessário):

```bash
cd /caminho/para/seu/projeto/pdf-union-php
composer install --no-dev --optimize-autoloader
```

3) Ajuste de permissões (somente para desenvolvimento local):

```bash
cd /caminho/para/seu/projeto/pdf-union-php
chmod -R 0777 arquivos resultado vendor
```

4) Verifique disponibilidade da função `exec()` (opcional)

O `process.php` tenta executar o `pdf-union.php` usando o mesmo binário PHP via `exec()` (usa a constante `PHP_BINARY`). Em muitos ambientes `exec()` está disponível; em provedores compartilhados pode estar desabilitado.

```bash
php -r "var_dump(function_exists('exec'));"
php -i | grep disable_functions
```

Comportamento quando `exec()` está desabilitado

O `process.php` foi implementado com dois modos:
- Modo A (padrão quando `exec()` disponível): chama o script `pdf-union.php` via CLI (mesmo binário PHP) e captura a saída.
- Modo B (fallback): quando `exec()` não está disponível, `process.php` faz `require_once 'pdf-union.php'` e chama a função `pdf_union_run()` exportada pelo script, executando a união dentro do mesmo processo PHP.

Isso permite rodar a aplicação em ambientes que bloqueiam `exec()` sem mudanças manuais, embora alguns hosts ainda restrinjam operações de arquivo/IO.

Executando em modo de desenvolvimento (servidor embutido PHP)

```bash
# PDF Union — Interface web (PHP)

Este repositório adiciona uma interface web em PHP ao script original `pdf-union.php`. A interface permite carregar PDFs e imagens, ordenar a sequência visualmente no navegador, gerar um PDF unificado e fazer o download do resultado.

Visão geral dos arquivos principais

- `index.php` — página web para upload, ordenação (arrastar/soltar) e envio para geração.
- `process.php` — recebe os arquivos na ordem definida pelo usuário, salva em `arquivos/` com prefixos numéricos (`1-nome.ext`, `2-nome.ext`, ...), aciona a rotina de união e apresenta o link de download.
- `download.php` — faz o streaming do PDF final para o cliente e remove o arquivo gerado e os uploads prefixados após o download.
- `cleanup.php` — limpa os diretórios `arquivos/` e `resultado/` e redireciona para a página inicial (opcional).

Checklist do comportamento implementado

- Aceita upload de arquivos (PDF, JPG, PNG) via `index.php`.
- Permite ordenar os arquivos no front-end; essa ordem é preservada nos arquivos salvos em `arquivos/` como prefixos numéricos.
- Executa o processo de união (`pdf-union.php`) e disponibiliza o download do resultado.
- Após o download, o arquivo gerado e os arquivos enviados com prefixos são removidos do servidor.

Requisitos recomendados

- PHP CLI 7.4 ou superior (PHP 8.x recomendado).
- Extensões úteis: `gd` (para manipulação de imagens), `mbstring`, `zip`.
- Composer para instalar dependências (`setasign/fpdi`, `setasign/fpdf`).

Diretórios usados

- `arquivos/` — arquivos enviados pelo usuário (salvos com prefixo numérico antes do processamento).
- `resultado/` — PDFs gerados pelo processo (nome padrão: `final_unificado_<timestamp>.pdf`).
- `vendor/` — dependências do Composer.

Instalação e execução (ex.: WSL / Ubuntu / Linux / macOS)

1) Instale PHP e Composer (exemplo para Ubuntu/WSL):

```bash
sudo apt update
sudo apt install -y php php-cli php-zip php-mbstring php-gd unzip curl
# instalar o composer (se ainda não tiver)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

2) Instale as dependências do projeto (caso ainda não exista a pasta `vendor/`):

```bash
cd /caminho/para/seu/projeto/pdf-union-php
composer install --no-dev --optimize-autoloader
```

3) Ajuste de permissões (apenas para desenvolvimento local):

```bash
cd /caminho/para/seu/projeto/pdf-union-php
chmod -R 0777 arquivos resultado vendor
```

Observação: em produção evite permissões 0777; prefira ajustar proprietário e permissões corretamente.

Verificar disponibilidade de `exec()`

O `process.php` tenta executar o `pdf-union.php` usando o mesmo binário PHP via `exec()` (constante `PHP_BINARY`). Alguns provedores desabilitam `exec()` por segurança. Para verificar:

```bash
php -r "var_dump(function_exists('exec'));"
php -i | grep disable_functions
```

Comportamento quando `exec()` está desabilitado

O `process.php` suporta dois modos de execução:

- Modo CLI (quando `exec()` está disponível): chama `pdf-union.php` como processo filho usando o mesmo binário PHP e captura a saída.
- Modo interno (fallback): quando `exec()` não está disponível, o `process.php` faz `require_once 'pdf-union.php'` e chama a função `pdf_union_run()` exportada pelo script, executando a união dentro do mesmo processo PHP.

Esse fallback permite rodar a aplicação em ambientes que bloqueiam `exec()`, embora permissões de arquivo e limitações do host ainda possam interferir.

Executando localmente com o servidor embutido do PHP

```bash
cd /caminho/para/seu/projeto/pdf-union-php
php -S 0.0.0.0:8000 -t .
```

Abra no navegador: http://localhost:8000/index.php

Executar com Docker / Docker Compose

Está incluído um `Dockerfile` e um `docker-compose.yml` de exemplo para facilitar a execução em container. A imagem usa Apache + PHP e instala extensões comuns (gd, zip, mbstring). Os diretórios `arquivos/` e `resultado/` são montados como volumes para persistência.

1) Construir e subir com Docker Compose:

```bash
docker-compose up -d --build
```

2) Acessar a aplicação:

Abra http://localhost:8000/index.php no navegador.

3) Comandos úteis dentro do container

Se precisar instalar dependências via Composer dentro do container (quando `vendor/` não foi copiado):

```bash
docker-compose run --rm web composer install --no-dev --optimize-autoloader
# ou, após o container já estar em execução:
docker-compose exec web composer install --no-dev --optimize-autoloader
```

4) Notas sobre permissões

- Os volumes mapeados mantêm os arquivos no host. Se houver problemas de permissão, ajuste o proprietário/permissões no host:

```bash
sudo chown -R $(id -u):$(id -g) arquivos resultado
```

- Em ambientes CI/CD ou servidores, prefira ajustar usuário/UID conforme a política do host.

5) Parar e remover containers

```bash
docker-compose down
```

Checklist Docker (implementado)

- `Dockerfile` criado (imagem baseada em `php:8.1-apache` com extensões necessárias).
- `docker-compose.yml` criado (mapeia portas e volumes, variável para composer).
- `.dockerignore` criado para reduzir o contexto de build.


Fluxo de uso (passo a passo)

1) Acesse `index.php`.
2) Envie arquivos (PDF, JPG, PNG) e ordene-os arrastando os itens na lista.
3) Clique em "Gerar PDF Unificado" para iniciar o processamento.
4) Ao final será apresentado um link para download do PDF unificado; ao baixar, o servidor remove o arquivo gerado e os uploads prefixados.

Segurança e recomendações para produção

- Valide tipos MIME e extensões dos arquivos recebidos.
- Limite o tamanho máximo de upload (`upload_max_filesize` e `post_max_size` no `php.ini`).
- Implemente autenticação/autorização se o serviço ficar disponível publicamente.
- Evite permissões amplas (0777) em produção; ajuste proprietário e permissões corretamente.
- Use HTTPS.

Resolução de problemas comuns

- Nenhum link de download aparece: verifique se há arquivos `final_unificado_*.pdf` em `resultado/` e as permissões de leitura/gravação.
- `exec()` desabilitado: o `process.php` tentará o fallback via `require` e `pdf_union_run()`. Se mesmo assim falhar, verifique os logs de erro do PHP e permissões dos diretórios.
- Problemas na conversão de imagens: confirme que a extensão `gd` está instalada e que os arquivos de imagem não estão corrompidos.
- Falta a biblioteca FPDI/FPDF: execute `composer install` para garantir que `vendor/` esteja presente.

Comandos úteis

```bash
# Verificar sintaxe de um arquivo PHP
php -l index.php

# Executar a união via CLI (modo original)
php pdf-union.php

# Rodar servidor de desenvolvimento
php -S 0.0.0.0:8000 -t .
```

Melhorias sugeridas (opcionais)

- Implementar barra de progresso real para upload usando XHR (`xhr.upload.onprogress`).
- Adicionar validações mais robustas (tipo MIME, tamanho, verificação de PDF válido).
- Melhorar a interface (mensagens de erro mais claras, pré-visualização de páginas, feedback do processamento).

Observação sobre o `pdf-union.php`

O script original foi mantido e levemente refatorado para expor a função `pdf_union_run()`, o que permite que o `process.php` o chame diretamente quando `exec()` não estiver disponível.

Teste em ambiente de desenvolvimento antes de mover para produção.
