# MySQL General Log Viewer 🗄️

Uma aplicação web moderna e responsiva para visualizar e analisar os logs gerais do MySQL de forma intuitiva e organizada.

![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![Security](https://img.shields.io/badge/security-audited-brightgreen)
![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)

---

## 🎬 Demonstração

> **Interface moderna e intuitiva para análise de logs MySQL**

### 🌟 Principais Destaques

- 🎨 Design gradiente moderno com animações suaves
- 🔐 Código auditado e protegido contra vulnerabilidades
- 💾 Configuração persistente (sem necessidade de reconfigurar)
- 🎯 Filtragem inteligente de usuários
- 📱 100% responsivo (desktop, tablet, mobile)

---

## 📋 Características

- ✨ **Interface Moderna**: Design limpo e responsivo com gradientes e animações
- 🔐 **Seguro**: Proteção contra SQL Injection e XSS
- 💾 **Configurável**: Salve credenciais do banco no navegador (localStorage)
- 🎯 **Filtros de Usuários**: Whitelist/Blacklist de usuários MySQL
- 📱 **Responsivo**: Funciona perfeitamente em desktop e mobile
- ⚡ **Single Page**: Carregamento dinâmico via AJAX
- 🎨 **Visual Atraente**: Gradientes modernos e ícones emoji

## 🚀 Funcionalidades

### Visualização de Logs
- Lista de threads ordenados por data
- Agrupamento de queries idênticas
- Contador de execuções por query
- Visualização completa de SQL
- Copiar queries com um clique

### Gerenciamento
- Habilitar/Desabilitar general_log
- Limpar logs com um clique
- Testar conexão antes de salvar
- Configuração persistente no navegador

### Filtros Avançados
- **Nenhum**: Mostra todos os usuários
- **Incluir apenas (Whitelist)**: Mostra SOMENTE usuários específicos
- **Excluir (Blacklist)**: Remove usuários específicos da visualização

## 📦 Requisitos

- PHP 7.0 ou superior
- MySQL 5.7 ou superior / MariaDB 10.2+
- Extensão PDO MySQL habilitada
- Navegador moderno com suporte a localStorage

## 🔧 Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/domwal/mysql-general-log-viewer.git
cd mysql-general-log-viewer
```

### 2. Configure o MySQL

#### Habilitar o General Log

Edite o arquivo de configuração do MySQL:

**Windows (XAMPP):**
```
C:\xampp\mysql\bin\my.ini
```

**Windows (WAMP - MariaDB):**
```
C:\wamp\bin\mariadb\mariadb10.6.22\my.ini
```

**Linux:**
```
/etc/mysql/mariadb.conf.d/50-server.cnf
```

Adicione as seguintes linhas na seção `[mysqld]`:

```ini
[mysqld]
general_log = 1
log_output = TABLE
general_log_file = mysql_query.log
```

Ou configure via linha de comando:

```sql
SET GLOBAL general_log = 1;
SET GLOBAL log_output = 'TABLE';
SET GLOBAL general_log_file = 'mysql_general_query.log';
```

#### Criar Usuário e Procedure

Execute os seguintes comandos no MySQL:

```sql
-- Criar usuário
CREATE USER 'userlog'@'%' IDENTIFIED BY 'userlog';
GRANT SELECT ON mysql.general_log TO 'userlog'@'%';

-- Permissão para limpar logs (opcional)
GRANT DROP ON mysql.general_log TO 'userlog'@'%';
FLUSH PRIVILEGES;

-- Criar procedure para habilitar/desabilitar log
DELIMITER //

CREATE PROCEDURE mysql.ToggleGeneralLog (IN log_state BOOLEAN)
BEGIN
    IF log_state THEN
        SET GLOBAL general_log = 1;
        SET GLOBAL log_output = 'table';
    ELSE 
        SET GLOBAL general_log = 0;
    END IF;
END // 

DELIMITER ;

-- Permissão para executar a procedure
GRANT EXECUTE ON PROCEDURE mysql.ToggleGeneralLog TO 'userlog'@'%';
```

### 3. Configure a aplicação

Abra o arquivo `mysql_log_view.php` e ajuste as credenciais padrão (linhas 94-97):

```php
// Valores padrão
$mySqlServerName = "127.0.0.1";
$mySqlUserName   = "root";
$mySqlPassword   = "";
$mySqlDbName     = 'mysql';
```

**⚠️ IMPORTANTE:** Para produção, NÃO use credenciais padrão. Configure suas próprias credenciais seguras.

### 4. Acesse a aplicação

Coloque o arquivo em seu servidor web e acesse:

```
http://localhost/mysql_log_view.php
```

## 🎯 Como Usar

### 🔌 Configurar Conexão

1. Clique no botão **⚙️ Configurar** no topo da página
2. Preencha os dados de conexão:
   - **Host**: Endereço do servidor MySQL (ex: localhost, 127.0.0.1)
   - **Usuário**: Nome de usuário do MySQL (ex: root, userlog)
   - **Senha**: Senha do usuário
3. **(Opcional)** Clique em **🔌 Testar Conexão** para verificar
4. Clique em **💾 Salvar** para armazenar no navegador

As credenciais ficarão salvas no localStorage do navegador e serão carregadas automaticamente na próxima vez!

### 👥 Configurar Filtros

1. Clique no botão **👥 Filtros** no topo da página
2. Selecione o tipo de filtro:
   - **Nenhum**: Mostra todos os usuários/conexões
   - **Incluir apenas (Whitelist)**: Mostra SOMENTE os usuários que você listar
   - **Excluir (Blacklist)**: Mostra todos EXCETO os usuários listados
3. Se escolheu include ou exclude, adicione os user_host:
   - Digite um user_host por linha
   - Formato: `usuario[usuario] @ host [ip]`
   - Exemplo: `root[root] @ localhost [127.0.0.1]`
4. Clique em **💾 Salvar Filtro**

**💡 Dica:** Use o botão **👤 Copiar User Host** (ao visualizar logs de um thread) para copiar o formato exato!

### 📊 Visualizar Logs

1. Na **barra lateral esquerda**, você verá a lista de **Thread IDs**
2. Clique em qualquer **Thread** para ver os detalhes
3. Você verá:
   - Tipo de comando (Query, Execute, etc)
   - Total de execuções (queries idênticas agrupadas)
   - Horário da execução
   - Query SQL completa
4. **Clique em qualquer query** para copiar para a área de transferência
5. Use **📄 Ver SQL Completa** para ver todas as queries do thread em sequência

### 🧹 Gerenciar Logs

- **🗑️ Limpar Todo o Log**: Remove TODOS os registros da tabela general_log
- **✅ Habilitar General Log**: Ativa o registro de logs (cuidado com performance!)
- **❌ Desabilitar General Log**: Desativa o registro (recomendado quando não usar)
- **🔄 Atualizar**: Recarrega a página com dados mais recentes

## 🔒 Segurança

Este projeto foi auditado e protegido contra:

- ✅ **SQL Injection** - Prepared statements e validação
- ✅ **XSS** - Escape completo de outputs
- ✅ **DoS** - Limites de entrada
- ✅ **Input Validation** - Whitelist e sanitização

### 🐛 Reportar Vulnerabilidades de Segurança

Se você descobrir uma vulnerabilidade de segurança:

1. **NÃO** abra uma issue pública
2. Envie um email privado descrevendo o problema
3. Aguarde resposta antes de divulgar publicamente
4. Será creditado pela descoberta (se desejar)

### ⚠️ Avisos de Segurança

**NÃO use em produção sem:**

1. HTTPS obrigatório
2. Sistema de autenticação
3. Headers de segurança (CSP, X-Frame-Options, etc)
4. Rate limiting
5. Configuração externa de credenciais (não deixe no código)
6. Firewall e restrição de IPs
7. Logs de auditoria

**Recomendações:**

- Use este projeto apenas em **ambiente de desenvolvimento**
- Configure credenciais específicas (não use root)
- Crie usuário MySQL com privilégios mínimos
- Monitore o uso e desabilite general_log quando não estiver usando
- Mantenha o PHP e MySQL atualizados

## 📁 Estrutura do Projeto

```
mysql-general-log-viewer/
├── mysql_log_view.php          # Aplicação principal
├── README.md                   # Este arquivo
├── LICENSE                     # Licença MIT
├── .gitignore                  # Arquivos ignorados pelo Git
├── LEIA-ME.txt                 # Instruções em português
└── PRIVACIDADE-GITHUB.md       # Análise de privacidade
```

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Faça um Fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

### Padrões de Código

- **PHP**: Use PSR-12, indentação de 4 espaços
- **JavaScript**: Use const/let, indentação de 4 espaços
- **SQL**: Palavras-chave em UPPERCASE
- **Segurança**: Sempre use prepared statements e escape de outputs

## 📝 Changelog

### v2.0.0 (2025-10-06)
- ✨ Interface moderna com gradientes
- 🔐 Correções de segurança (SQL Injection, XSS)
- 💾 Configuração persistente no navegador
- 🎯 Sistema de filtros de usuários
- 📱 Design responsivo
- ⚡ Single page com AJAX

### v1.0.0
- 📊 Versão inicial básica

## 🐛 Problemas Conhecidos

- localStorage não funciona em modo anônimo/privado
- Requer JavaScript habilitado
- Senhas armazenadas em base64 (não é criptografia real)

### 🔧 Troubleshooting

**Erro de Conexão:**
- Verifique se o MySQL está rodando
- Confirme host, usuário e senha
- Teste se a extensão PDO MySQL está habilitada: `php -m | grep pdo_mysql`

**Nenhum Log Aparece:**
- Verifique se general_log está habilitado: `SHOW VARIABLES LIKE 'general_log';`
- Confirme que log_output está como 'TABLE'
- Execute algumas queries para gerar logs

**Página em Branco:**
- Verifique erros no console do navegador (F12)
- Ative error_reporting no PHP
- Verifique permissões de arquivo

**Filtros Não Funcionam:**
- Limpe o cache do navegador
- Verifique se o user_host está no formato correto
- Use o botão "Copiar User Host" para garantir o formato

## ❓ FAQ (Perguntas Frequentes)

**P: É seguro usar este projeto?**
R: Sim, o código foi auditado e protegido contra SQL Injection e XSS. Porém, para produção, adicione autenticação e HTTPS.

**P: Posso usar em produção?**
R: Não recomendado. Este projeto é ideal para desenvolvimento e debug local. Para produção, implemente autenticação, HTTPS e outras camadas de segurança.

**P: O general_log impacta a performance?**
R: Sim! O general_log registra TODAS as queries e pode impactar significativamente a performance. Use apenas para debug e desabilite quando não precisar.

**P: Como faço backup dos logs?**
R: Use o botão de exportar (quando implementado) ou execute: `SELECT * FROM mysql.general_log INTO OUTFILE '/tmp/backup.csv';`

**P: Posso ver logs de outros bancos além do MySQL?**
R: Não. Este projeto é específico para MySQL/MariaDB general_log.

**P: As credenciais salvas no navegador são seguras?**
R: São codificadas em base64 (não criptografadas). Não use em computadores compartilhados. Para mais segurança, não salve as credenciais.

**P: Funciona com MySQL 8.0?**
R: Sim! Compatível com MySQL 5.7+, 8.0+ e MariaDB 10.2+.

**P: Posso contribuir com o projeto?**
R: Sim! Pull requests são bem-vindos. Veja a seção "Contribuindo" acima.

## 💡 Roadmap

### Em Desenvolvimento
- [ ] Sistema de autenticação básico
- [ ] Exportar logs (CSV, JSON, SQL)
- [ ] Pesquisa/filtro de queries

### Planejado
- [ ] Gráficos de estatísticas e performance
- [ ] Análise de queries lentas
- [ ] Favoritar/salvar queries importantes
- [ ] Comparação de queries
- [ ] Modo escuro

### Futuro
- [ ] Múltiplos idiomas (i18n)
- [ ] API REST para integração
- [ ] Dashboard com widgets personalizáveis
- [ ] Alertas e notificações
- [ ] Integração com ferramentas de monitoramento

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 👤 Autor

Desenvolvido com ❤️ para facilitar a análise de logs MySQL

## 🙏 Agradecimentos

- Comunidade PHP
- Comunidade MySQL/MariaDB
- Todos os contribuidores

## 📞 Suporte

Se encontrar problemas:

1. Revise a documentação neste README
2. Verifique as credenciais do MySQL
3. Teste a conexão usando o botão "Testar Conexão"
4. Consulte as [issues existentes](https://github.com/domwal/mysql-general-log-viewer/issues)
5. Abra uma nova issue com detalhes do problema

---

**⚡ Tip:** Use em ambiente de desenvolvimento para debugar queries! Nunca deixe general_log habilitado em produção - impacta performance.

**🔒 Security:** Este é um projeto open-source. Revise o código antes de usar em produção!
