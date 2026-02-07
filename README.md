# Vanilla PHP HOA - Docker Setup

Projeto PHP vanilla com PostgreSQL rodando em Docker com Nginx e Xdebug configurado.

## Requisitos

- Docker
- Docker Compose
- VSCode com extensão PHP Debug (para usar o Xdebug)

## Estrutura do Projeto

```
.
├── Dockerfile              # Imagem PHP-FPM com Xdebug
├── docker-compose.yml      # Orquestração dos containers
├── nginx/
│   └── default.conf       # Configuração do Nginx
├── config/
│   └── database.php       # Conexão com PostgreSQL
├── .env                    # Variáveis de ambiente
└── .vscode/
    └── launch.json        # Configuração do Xdebug para VSCode
```

## Como Usar

### 1. Iniciar os containers

```bash
docker-compose up -d
```

Isso irá:
- Construir a imagem PHP com Xdebug
- Iniciar o container Nginx
- Iniciar o container PostgreSQL
- Criar as tabelas e usuário admin automaticamente

### 2. Acessar a aplicação

Abra o navegador em: http://localhost:8080

**Credenciais padrão:**
- Usuário: `admin`
- Senha: `admin`

### 3. Verificar os logs

```bash
# Todos os containers
docker-compose logs -f

# Container específico
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f postgres
```

### 4. Parar os containers

```bash
docker-compose down
```

### 5. Parar e remover volumes (dados do banco)

```bash
docker-compose down -v
```

## Usando o Xdebug

### Configuração no VSCode

1. A configuração já está em `.vscode/launch.json`
2. Instale a extensão "PHP Debug" no VSCode (felixfbecker.php-debug)
3. Pressione `F5` ou vá em "Run > Start Debugging"
4. Coloque breakpoints no código PHP
5. Acesse a aplicação no navegador
6. O VSCode irá pausar nos breakpoints

### Configuração do Xdebug

O Xdebug já está configurado com:
- Porta: 9003
- IDE Key: VSCODE
- Client host: host.docker.internal
- Mode: debug
- Start with request: yes

### Troubleshooting Xdebug

Se o Xdebug não estiver funcionando:

```bash
# Verificar se o Xdebug está instalado
docker-compose exec php php -v

# Ver logs do Xdebug
docker-compose exec php cat /var/log/xdebug.log

# Verificar configuração do Xdebug
docker-compose exec php php -i | grep xdebug
```

## Banco de Dados

### Conectar ao PostgreSQL

```bash
docker-compose exec postgres psql -U postgres -d vanilla
```

### Configurações do Banco

As configurações podem ser alteradas no arquivo `.env`:

```env
DB_HOST=postgres
DB_PORT=5432
DB_NAME=vanilla
DB_USER=postgres
DB_PASSWORD=postgres
```

### Backup do Banco

```bash
docker-compose exec postgres pg_dump -U postgres vanilla > backup.sql
```

### Restaurar Backup

```bash
docker-compose exec -T postgres psql -U postgres vanilla < backup.sql
```

## Comandos Úteis

### Reconstruir a imagem PHP

```bash
docker-compose build --no-cache php
docker-compose up -d
```

### Acessar o container PHP

```bash
docker-compose exec php bash
```

### Acessar o container Nginx

```bash
docker-compose exec nginx sh
```

### Ver processos rodando

```bash
docker-compose ps
```

### Limpar tudo (containers, volumes, imagens)

```bash
docker-compose down -v --rmi all
```

## Portas Expostas

- **8080**: Nginx (HTTP)
- **5432**: PostgreSQL
- **9003**: Xdebug

## Desenvolvimento

### Hot Reload

Os arquivos do projeto estão montados como volumes nos containers, então qualquer alteração no código é refletida imediatamente sem precisar reiniciar os containers.

### Instalando Dependências PHP

Se precisar instalar dependências via Composer:

```bash
docker-compose exec php composer install
```

## Segurança

**IMPORTANTE**: Este setup é para desenvolvimento local. Para produção:
- Altere as senhas padrão
- Desabilite o Xdebug
- Configure HTTPS
- Use secrets do Docker para credenciais
- Revise as configurações de segurança do Nginx

## Licença

MIT
