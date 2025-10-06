<?php
    header('Content-Type: text/html; charset=utf-8');
    ini_set('memory_limit', '1024M');

    function InstrucoesTexto($tipo) { 
        if ($tipo == 'criar_usuario_procedure') {
            $texto = '';
            $texto .= "<br>-- <strong>Para criar usuario para acessar os logs e limpar o log, executar os seguintes comandos no mysql:</strong>";
            $texto .= "<br>";
            $texto .= "<br>CREATE USER 'userlog'@'%' IDENTIFIED BY 'userlog';";
            $texto .= "<br>GRANT SELECT ON mysql.general_log TO 'userlog'@'%';";
            $texto .= "<br>";
            $texto .= "<br>-- <strong>Para permitir limpar</strong>";
            $texto .= "<br>";
            $texto .= "<br>GRANT DROP ON mysql.general_log TO 'userlog'@'%';";
            $texto .= "<br>FLUSH PRIVILEGES;";
            $texto .= "<br>";
            $texto .= "<br>-- <strong>Para poder alterar o general_log por aqui, é necessário criar essa procedure no mysql</strong>";
            $texto .= "<br>";
            $texto .= "<br>DELIMITER //";
            $texto .= "<br>";
            $texto .= "<br>CREATE PROCEDURE mysql.ToggleGeneralLog ( IN log_state BOOLEAN )";
            $texto .= "<br>BEGIN";
            $texto .= "<br>    IF log_state THEN";
            $texto .= "<br>        SET GLOBAL general_log = 1;";
            $texto .= "<br>        SET global log_output = 'table';";
            $texto .= "<br>    ELSE ";
            $texto .= "<br>        SET GLOBAL general_log = 0;";
            $texto .= "<br>    END IF;";
            $texto .= "<br>    ";
            $texto .= "<br>END // ";
            $texto .= "<br>";
            $texto .= "<br>DELIMITER;";
            $texto .= "<br>";
            $texto .= "<br>-- <strong>Permissão para o usuário executar a procedure acima</strong>";
            $texto .= "<br>";
            $texto .= "<br>GRANT EXECUTE ON PROCEDURE mysql.ToggleGeneralLog TO 'userlog'@'%';";

            return $texto;
        }
        elseif ($tipo == 'habilitar_log') {
            $texto = '';
            $texto .= "- Editar o arquivo:";
            $texto .= "<br> Windows: C:\\xampp\\mysql\\bin\\my.ini";
            $texto .= "<br> Windows Wamp Mariadb: C:\\wamp\\bin\\mariadb\\mariadb10.6.22\\my.ini";
            $texto .= "<br> Windows Xampp MySql: C:\\wamp\\bin\\mysql\\mysql8.0.33\\my.ini";
            $texto .= "<br> Linux: /etc/mysql/mariadb.conf.d/50-server.cnf";
            $texto .= "<br>----------------------------------------";
            $texto .= "<br>[mysqld]";
            $texto .= "<br>... ";
            $texto .= "<br>general_log=1";
            $texto .= "<br>general_log_file=mysql_query.log";
            $texto .= "<br>log_output = 'TABLE'";
            $texto .= "<br>----------------------------------------";
            $texto .= "<br>Ou em linha de comando";
            $texto .= "<br>----------------------------------------";
            $texto .= "<br>SET global general_log = 1;";
            $texto .= "<br>SET global log_output = 'table';";
            $texto .= "<br>SET global general_log_file = 'mysql_general_query.log';";
            return $texto;
        }
    }

    // Handle AJAX requests
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        
        if ($_GET['ajax'] === 'test_connection') {
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $testConn = new PDO(
                    "mysql:host={$data['host']};dbname=mysql", 
                    $data['username'], 
                    $data['password']
                );
                $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                echo json_encode(['success' => true, 'message' => 'Conexão bem-sucedida!']);
            } catch(PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
            }
            exit();
        }
    }

    // MySql Configuration - Aceita POST para configuração dinâmica
    if (isset($_POST['db_host']) && isset($_POST['db_username']) && isset($_POST['db_password'])) {
        $mySqlServerName = $_POST['db_host'];
        $mySqlUserName   = $_POST['db_username'];
        $mySqlPassword   = $_POST['db_password'];
        $mySqlDbName     = 'mysql';
    } else {
        // Valores padrão
        $mySqlServerName = "127.0.0.1";
        $mySqlUserName   = "root";
        $mySqlPassword   = "";
        $mySqlDbName     = 'mysql';
    }

    // show logs from mysql users - Aceita configuração via POST com validação
    if (isset($_POST['filter_type']) && isset($_POST['filter_users'])) {
        // Whitelist validation for filter_type
        $allowedFilterTypes = ['include', 'exclude', 'none'];
        $filterType = in_array($_POST['filter_type'], $allowedFilterTypes, true) ? $_POST['filter_type'] : 'none';
        
        // Decode and validate JSON
        $filterUsers = json_decode($_POST['filter_users'], true);
        
        // Ensure it's an array and sanitize each entry
        if (!is_array($filterUsers)) {
            $filterUsers = [];
        }
        
        // Limit number of filter entries to prevent DoS
        $filterUsers = array_slice($filterUsers, 0, 100);
        
        // Sanitize each user entry (remove potential harmful characters)
        $filterUsers = array_map(function($user) {
            // Allow only alphanumeric, @, [], spaces, dots, hyphens, underscores, colons
            return preg_replace('/[^a-zA-Z0-9@\[\]\s.\-_:]/u', '', $user);
        }, $filterUsers);
        
        if ($filterType === 'include') {
            $mysqlUserHost = $filterUsers;
            $notMysqlUserHost = [];
        } elseif ($filterType === 'exclude') {
            $mysqlUserHost = [];
            $notMysqlUserHost = $filterUsers;
        } else {
            $mysqlUserHost = [];
            $notMysqlUserHost = [];
        }
    } else {
        // Valores padrão
        $mysqlUserHost = [];
        $notMysqlUserHost = [];
    }

    $connectionError = null;
    try {
        $conn = new PDO("mysql:host=$mySqlServerName;dbname={$mySqlDbName}", $mySqlUserName, $mySqlPassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("set names utf8");
    } catch(PDOException $e) {
        $connectionError = $e->getMessage();
        $conn = null;
    }

?><!DOCTYPE html>
<html lang="pt-br">
<head>
    <title>MySQL General Log Viewer</title>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style type="text/css">
        :root {
            --primary-color: #2563eb;
            --secondary-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f3f4f6;
            --border-color: #e5e7eb;
            --text-color: #374151;
            --bg-color: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--text-color);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .button, .button:link, .button:visited {
            border: none;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-radius: 8px;
            cursor: pointer;
            white-space: nowrap;
        }

        .button-primary {
            background-color: var(--primary-color);
        }
        
        .button-success {
            background-color: var(--secondary-color);
        }
        
        .button-danger {
            background-color: var(--danger-color);
        }
        
        .button-warning {
            background-color: var(--warning-color);
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            opacity: 0.9;
        }

        .button:active {
            transform: translateY(0);
        }

        .main-content {
            display: flex;
            gap: 0;
            min-height: calc(100vh - 200px);
        }

        .sidebar {
            width: 300px;
            background: var(--light-color);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }

        .content-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }

        .thread-list {
            list-style: none;
        }

        .thread-item {
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        }

        .thread-item:hover {
            background: white;
        }

        .thread-link {
            display: block;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.2s;
        }

        .thread-link:hover {
            color: var(--primary-color);
            padding-left: 25px;
        }

        .thread-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .thread-id {
            font-weight: 600;
            font-size: 16px;
            color: var(--primary-color);
        }

        .thread-count {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .thread-time {
            font-size: 12px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }

        th {
            background: linear-gradient(135deg, var(--dark-color) 0%, #374151 100%);
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        th a {
            color: white;
            text-decoration: none;
        }

        th a:hover {
            text-decoration: underline;
        }

        td {
            border: 1px solid var(--border-color);
            padding: 10px 12px;
            font-size: 14px;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tr:hover {
            background-color: #f3f4f6;
        }

        .centralizado {
            text-align: center;
        }

        .copyTo {
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .copyTo:hover {
            background: var(--light-color);
        }

        .newTextArea {
            position: fixed;
            top: 0px;
            left: 0px;
            width: 2em;
            height: 2em;
            padding: 0px;
            border: 0px;
            outline: none;
            box-shadow: none;
            background: transparent;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .alert {
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger-color);
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--secondary-color);
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid var(--primary-color);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 20px;
            font-weight: 600;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .info-box {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid var(--primary-color);
        }

        .collapsible {
            background-color: var(--light-color);
            color: var(--dark-color);
            cursor: pointer;
            padding: 15px;
            width: 100%;
            border: none;
            text-align: left;
            outline: none;
            font-size: 15px;
            font-weight: 500;
            border-radius: 8px;
            margin: 10px 0;
            transition: background-color 0.3s;
        }

        .collapsible:hover {
            background-color: #e5e7eb;
        }

        .collapsible-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: white;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .collapsible-content.active {
            max-height: 1000px;
            padding: 15px;
            border: 1px solid var(--border-color);
        }

        .sql-block {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                max-height: 300px;
            }

            .content-area {
                max-height: none;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
            }

            .button {
                width: 100%;
                justify-content: center;
            }
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        .no-data-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
    <script type="text/javascript">
        // Database Configuration Management
        const DbConfig = {
            save: function(host, username, password) {
                localStorage.setItem('mysql_host', host);
                localStorage.setItem('mysql_username', username);
                localStorage.setItem('mysql_password', btoa(password)); // Basic encoding
                localStorage.setItem('mysql_config_saved', 'true'); // Flag para indicar que há config salva
            },
            load: function() {
                return {
                    host: localStorage.getItem('mysql_host') || '127.0.0.1',
                    username: localStorage.getItem('mysql_username') || 'root',
                    password: localStorage.getItem('mysql_password') ? atob(localStorage.getItem('mysql_password')) : ''
                };
            },
            hasSavedConfig: function() {
                return localStorage.getItem('mysql_config_saved') === 'true';
            },
            clear: function() {
                localStorage.removeItem('mysql_host');
                localStorage.removeItem('mysql_username');
                localStorage.removeItem('mysql_password');
                localStorage.removeItem('mysql_config_saved'); // Remover a flag também
            }
        };

        // User Filter Configuration Management
        const UserFilter = {
            save: function(filterType, users) {
                localStorage.setItem('mysql_filter_type', filterType); // 'include', 'exclude' ou 'none'
                localStorage.setItem('mysql_filter_users', JSON.stringify(users));
                localStorage.setItem('mysql_filter_saved', 'true');
            },
            load: function() {
                return {
                    type: localStorage.getItem('mysql_filter_type') || 'none',
                    users: localStorage.getItem('mysql_filter_users') ? JSON.parse(localStorage.getItem('mysql_filter_users')) : []
                };
            },
            hasSavedFilter: function() {
                return localStorage.getItem('mysql_filter_saved') === 'true';
            },
            clear: function() {
                localStorage.removeItem('mysql_filter_type');
                localStorage.removeItem('mysql_filter_users');
                localStorage.removeItem('mysql_filter_saved');
            }
        };

        // Modal Management
        const Modal = {
            open: function(modalId) {
                document.getElementById(modalId).style.display = 'block';
            },
            close: function(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }
        };

        // Copy to clipboard function
        function copyTextToClipboard(text) {
            var textArea = document.createElement("textarea");
            textArea.className = 'newTextArea';
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showNotification('Texto copiado!', 'success');
                }
            } catch (err) {
               showNotification('Erro ao copiar o texto', 'error');
            }
            document.body.removeChild(textArea);
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#2563eb'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Clear log function
        function limparLog() {
            if (confirm("Deseja Realmente Limpar Todo o General Log do Mysql?")) {
                loadContent('Limpar');
            }
        }

        // Copy user_host to clipboard
        function copyUserHost(threadId) {
            const userHost = document.querySelector(`.thread-row[data-thread='${threadId}']`).dataset.user;
            if (userHost) {
                copyTextToClipboard(userHost);
                showNotification('User Host copiado: ' + userHost, 'success');
            } else {
                showNotification('Nenhum user_host disponível', 'error');
            }
        }

        // Load content via AJAX
        function loadContent(threadId, orderNum = 0) {
            const contentArea = document.getElementById('content-area');
            contentArea.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading" style="margin: 0 auto;"></div><p style="margin-top: 20px;">Carregando...</p></div>';

            const config = DbConfig.load();
            const filter = UserFilter.load();
            const formData = new FormData();
            formData.append('thread_id', threadId);
            formData.append('ordernum', orderNum);
            formData.append('db_host', config.host);
            formData.append('db_username', config.username);
            formData.append('db_password', config.password);
            formData.append('filter_type', filter.type);
            formData.append('filter_users', JSON.stringify(filter.users));

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Extract only the content we need
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const content = doc.querySelector('#ajax-content');
                if (content) {
                    contentArea.innerHTML = content.innerHTML;
                    attachEventListeners();
                } else {
                    contentArea.innerHTML = html;
                }
            })
            .catch(error => {
                contentArea.innerHTML = '<div class="alert alert-danger">Erro ao carregar conteúdo: ' + error + '</div>';
            });
        }

        // Test database connection
        function testConnection() {
            const host = document.getElementById('db_host').value;
            const username = document.getElementById('db_username').value;
            const password = document.getElementById('db_password').value;
            const statusDiv = document.getElementById('connection-status');

            statusDiv.innerHTML = '<div class="loading"></div> Testando conexão...';

            fetch('?ajax=test_connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ host, username, password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="alert alert-danger">Erro: ' + error + '</div>';
            });
        }

        // Save database configuration
        function saveDbConfig() {
            const host = document.getElementById('db_host').value;
            const username = document.getElementById('db_username').value;
            const password = document.getElementById('db_password').value;

            if (!host || !username) {
                showNotification('Por favor, preencha host e usuário!', 'error');
                return;
            }

            DbConfig.save(host, username, password);
            showNotification('Configuração salva! Recarregando...', 'success');
            Modal.close('configModal');
            
            // Reload the page with new configuration
            setTimeout(() => {
                // Forçar reload sem cache
                window.location.href = window.location.pathname;
            }, 1000);
        }

        // Clear saved configuration
        function clearDbConfig() {
            if (confirm('Deseja limpar as credenciais salvas e usar as padrões?')) {
                console.log('Limpando credenciais do localStorage...');
                DbConfig.clear();
                console.log('Credenciais limpas. hasSavedConfig:', DbConfig.hasSavedConfig());
                Modal.close('configModal');
                showNotification('Credenciais limpas! Recarregando...', 'success');
                setTimeout(() => {
                    // Redirecionar para a página sem parâmetros para garantir recarga limpa
                    console.log('Redirecionando para:', window.location.pathname);
                    window.location.href = window.location.pathname;
                }, 500);
            }
        }

        // Load database configuration into modal
        function loadDbConfig() {
            const config = DbConfig.load();
            document.getElementById('db_host').value = config.host;
            document.getElementById('db_username').value = config.username;
            document.getElementById('db_password').value = config.password;
        }

        // Load user filter configuration
        function loadUserFilter() {
            const filter = UserFilter.load();
            document.getElementById('filter_type').value = filter.type;
            document.getElementById('filter_users').value = filter.users.join('\n');
            updateFilterTypeUI();
        }

        // Update UI based on filter type
        function updateFilterTypeUI() {
            const filterType = document.getElementById('filter_type').value;
            const userListDiv = document.getElementById('user_list_container');
            const filterInfo = document.getElementById('filter_info');
            
            if (filterType === 'none') {
                userListDiv.style.display = 'none';
                filterInfo.innerHTML = '<div class="alert alert-info">📌 Mostrando TODOS os usuários</div>';
            } else if (filterType === 'include') {
                userListDiv.style.display = 'block';
                filterInfo.innerHTML = '<div class="alert alert-success">✅ Mostrando APENAS os usuários listados</div>';
            } else if (filterType === 'exclude') {
                userListDiv.style.display = 'block';
                filterInfo.innerHTML = '<div class="alert alert-danger">⛔ EXCLUINDO os usuários listados</div>';
            }
        }

        // Save user filter configuration
        function saveUserFilter() {
            const filterType = document.getElementById('filter_type').value;
            const filterUsersText = document.getElementById('filter_users').value;
            const users = filterUsersText.split('\n')
                .map(u => u.trim())
                .filter(u => u.length > 0);
            
            UserFilter.save(filterType, users);
            showNotification('Filtro salvo! Recarregando...', 'success');
            Modal.close('filterModal');
            
            setTimeout(() => {
                window.location.href = window.location.pathname;
            }, 1000);
        }

        // Clear user filter
        function clearUserFilter() {
            if (confirm('Deseja limpar o filtro de usuários?')) {
                UserFilter.clear();
                showNotification('Filtro limpo! Recarregando...', 'success');
                Modal.close('filterModal');
                
                setTimeout(() => {
                    window.location.href = window.location.pathname;
                }, 500);
            }
        }

        // Attach event listeners to dynamic content
        function attachEventListeners() {
            // Copy text functionality
            document.querySelectorAll('.copyTo').forEach(el => {
                el.onclick = function() {
                    let textContent = this.innerHTML;
                    textContent = textContent.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
                    copyTextToClipboard(textContent);
                };
            });

            // Collapsible sections
            document.querySelectorAll('.collapsible').forEach(el => {
                el.onclick = function() {
                    this.classList.toggle('active');
                    const content = this.nextElementSibling;
                    content.classList.toggle('active');
                };
            });
        }

        // Check if we need to reload with saved credentials
        function checkAndApplyCredentials() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasSaved = DbConfig.hasSavedConfig();
            const hasSavedFilter = UserFilter.hasSavedFilter();
            const hasApplied = urlParams.has('credentials_applied');
            
            console.log('checkAndApplyCredentials - hasSavedConfig:', hasSaved, 'hasSavedFilter:', hasSavedFilter, 'credentials_applied:', hasApplied);
            
            // Verificar se há credenciais salvas ou filtros salvos
            if ((hasSaved || hasSavedFilter) && !hasApplied) {
                const config = DbConfig.load();
                const filter = UserFilter.load();
                
                console.log('Aplicando credenciais salvas:', config.username + '@' + config.host);
                console.log('Aplicando filtro:', filter.type, 'com', filter.users.length, 'usuários');
                
                // Recarregar a página com as credenciais via POST
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.pathname + '?credentials_applied=1';
                
                const fields = {
                    'db_host': config.host,
                    'db_username': config.username,
                    'db_password': config.password,
                    'filter_type': filter.type,
                    'filter_users': JSON.stringify(filter.users)
                };
                
                for (const key in fields) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
                return true;
            }
            
            if (!hasSaved && !hasSavedFilter) {
                console.log('Nenhuma credencial ou filtro salvo. Usando padrões do PHP.');
            }
            
            return false;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar se precisa aplicar credenciais salvas
            if (checkAndApplyCredentials()) {
                return; // Página será recarregada
            }
            
            // Load saved configuration
            loadDbConfig();
            loadUserFilter();
            
            // Attach event listeners
            attachEventListeners();

            // Close modal when clicking outside
            window.onclick = function(event) {
                const configModal = document.getElementById('configModal');
                const filterModal = document.getElementById('filterModal');
                if (event.target == configModal) {
                    Modal.close('configModal');
                }
                if (event.target == filterModal) {
                    Modal.close('filterModal');
                }
            };
        });
    </script>
</head>
<body>

<!-- Configuration Modal -->
<div id="configModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>⚙️ Configuração do Banco de Dados</h2>
            <span class="close" onclick="Modal.close('configModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="db_host">Host:Porta</label>
                <input type="text" id="db_host" placeholder="127.0.0.1">
            </div>
            <div class="form-group">
                <label for="db_username">Usuário</label>
                <input type="text" id="db_username" placeholder="root">
            </div>
            <div class="form-group">
                <label for="db_password">Senha</label>
                <input type="password" id="db_password" placeholder="">
            </div>
            <div id="connection-status"></div>
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="button button-primary" onclick="testConnection()" style="flex: 1;">🔌 Testar Conexão</button>
                <button class="button button-success" onclick="saveDbConfig()" style="flex: 1;">💾 Salvar</button>
            </div>
            <div style="margin-top: 15px; text-align: center;">
                <button class="button button-danger" onclick="clearDbConfig()" style="width: 100%;">🗑️ Limpar Credenciais Salvas</button>
            </div>
        </div>
    </div>
</div>

<!-- User Filter Modal -->
<div id="filterModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2>👥 Filtro de Usuários MySQL</h2>
            <span class="close" onclick="Modal.close('filterModal')">&times;</span>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="filter_type">Tipo de Filtro</label>
                <select id="filter_type" onchange="updateFilterTypeUI()" style="width: 100%; padding: 10px 12px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                    <option value="none">Nenhum (mostrar todos)</option>
                    <option value="include">Incluir apenas (whitelist)</option>
                    <option value="exclude">Excluir (blacklist)</option>
                </select>
            </div>

            <div id="filter_info" style="margin: 15px 0;"></div>

            <div id="user_list_container" style="display: none;">
                <div class="form-group">
                    <label for="filter_users">
                        Lista de Usuários
                        <small style="color: #6b7280; font-weight: normal;">(um por linha)</small>
                    </label>
                    <textarea 
                        id="filter_users" 
                        rows="12" 
                        placeholder="Exemplo:&#10;root[root] @ localhost [127.0.0.1]&#10;bztech[bztech] @ localhost [::1]&#10;user[user] @ localhost [192.168.0.100]"
                        style="font-family: 'Courier New', monospace; font-size: 12px;"
                    ></textarea>
                    <small style="color: #6b7280; display: block; margin-top: 8px;">
                        💡 <strong>Dica:</strong> Copie o formato exato do "user_host" que aparece nos logs do MySQL.<br>
                        Exemplo: <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">root[root] @ localhost [127.0.0.1]</code>
                    </small>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button class="button button-success" onclick="saveUserFilter()" style="flex: 1;">💾 Salvar Filtro</button>
                <button class="button button-danger" onclick="clearUserFilter()" style="flex: 1;">🗑️ Limpar Filtro</button>
            </div>

            <div class="info-box" style="margin-top: 20px; font-size: 13px;">
                <strong>ℹ️ Como usar:</strong><br>
                • <strong>Nenhum:</strong> Mostra todos os threads de todos os usuários<br>
                • <strong>Incluir apenas:</strong> Mostra SOMENTE os threads dos usuários listados<br>
                • <strong>Excluir:</strong> Mostra todos os threads EXCETO dos usuários listados
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="header">
        <div>
            <h1>🗄️ MySQL General Log Viewer</h1>
            <?php if (isset($_POST['db_host'])): ?>
                <small style="opacity: 0.9; font-size: 13px;">
                    📡 Conectado: <?php echo htmlspecialchars($_POST['db_username'] . '@' . $_POST['db_host'], ENT_QUOTES, 'UTF-8'); ?>
                </small>
            <?php else: ?>
                <small style="opacity: 0.9; font-size: 13px;">
                    📡 Usando credenciais padrão (<?php echo htmlspecialchars($mySqlUserName . '@' . $mySqlServerName, ENT_QUOTES, 'UTF-8'); ?>)
                </small>
            <?php endif; ?>
        </div>
        <div class="header-actions">
            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="button button-primary">🏠 Início</a>
            <button class="button button-warning" onclick="Modal.open('filterModal')">👥 Filtros</button>
            <button class="button button-primary" onclick="Modal.open('configModal')">⚙️ Configurar</button>
            <button class="button button-success" onclick="location.reload()">🔄 Atualizar</button>
        </div>
    </div>

<?php if ($connectionError): ?>
    <div class="alert alert-danger" style="margin: 20px;">
        <strong>❌ Erro de Conexão:</strong> <?php echo htmlspecialchars($connectionError, ENT_QUOTES, 'UTF-8'); ?>
        <br><br>
        <button class="button button-primary" onclick="Modal.open('configModal')">⚙️ Configurar Conexão</button>
    </div>
    <div style="padding: 20px;">
        <button class="collapsible">📋 Instruções para criar usuário e procedure no MySQL</button>
        <div class="collapsible-content">
            <?php echo InstrucoesTexto('criar_usuario_procedure'); ?>
        </div>
        
        <button class="collapsible">🔧 Instruções para habilitar o General Log</button>
        <div class="collapsible-content">
            <?php echo InstrucoesTexto('habilitar_log'); ?>
        </div>
    </div>
<?php else: ?>
    <div class="main-content">
        <?php if (empty($_POST['thread_id'])): ?>
        <!-- Sidebar with thread list -->
        <div class="sidebar">
            <ul class="thread-list">
                <?php
                // Build secure WHERE conditions with parameterized queries
                $whereCond = '';
                $params = [];
                
                if (!empty($mysqlUserHost)) {
                    // Escape each user_host value to prevent SQL injection
                    $escapedUsers = array_map(function($user) use ($conn) {
                        return $conn->quote($user);
                    }, $mysqlUserHost);
                    $whereCond .= "\n                ";
                    $whereCond .= "AND user_host IN (" . implode(", ", $escapedUsers) . ")";
                }
                if (!empty($notMysqlUserHost)) {
                    // Escape each user_host value to prevent SQL injection
                    $escapedUsers = array_map(function($user) use ($conn) {
                        return $conn->quote($user);
                    }, $notMysqlUserHost);
                    $whereCond .= "\n                ";
                    $whereCond .= "AND user_host NOT IN (" . implode(", ", $escapedUsers) . ")";
                }

                $selectString = "
                    SELECT
                        thread_id,
                        COUNT( * ) AS total,
                        DATE_FORMAT(event_time, '%d/%m/%Y %H:%i:%s') AS event_time_br,
                        user_host
                    FROM
                        `mysql`.`general_log` 
                    WHERE
                        command_type NOT IN ('Quit', 'Connect') {$whereCond}
                    GROUP BY
                        thread_id 
                    ORDER BY
                        thread_id DESC 
                        LIMIT 50
                ";

                $stmt = $conn->prepare($selectString);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($result) > 0) {
                    foreach ($result as $value) {
                        $threadIdSafe = (int)$value['thread_id'];
                        $userHostSafe = htmlspecialchars($value['user_host'], ENT_QUOTES, 'UTF-8');
                        $totalSafe = (int)$value['total'];
                        $timeSafe = htmlspecialchars($value['event_time_br'], ENT_QUOTES, 'UTF-8');
                        
                        echo "<li class='thread-item'>";
                        echo "  <a href='javascript:void(0);' onclick='loadContent({$threadIdSafe}, 2)' class='thread-link' title='{$userHostSafe}'>";
                        echo "    <div class='thread-info'>";
                        echo "      <span class='thread-id'>Thread #{$threadIdSafe}</span>";
                        echo "      <span class='thread-count'>{$totalSafe}</span>";
                        echo "    </div>";
                        echo "    <div class='thread-time'>🕒 {$timeSafe}</div>";
                        echo "  </a>";
                        echo "</li>";
                    }
                } else {
                    echo "<li class='thread-item'>";
                    echo "  <div class='no-data'>";
                    echo "    <div class='no-data-icon'>📭</div>";
                    echo "    <div>Nenhum registro encontrado</div>";
                    echo "  </div>";
                    echo "</li>";
                }
                ?>
            </ul>
        </div>

        <!-- Main content area -->
        <div class="content-area" id="content-area">
            <div class="alert alert-info">
                <strong>👋 Bem-vindo!</strong><br>
                Selecione um Thread ID na barra lateral para visualizar os logs.
            </div>

            <div style="margin-top: 20px;">
                <button class="button button-danger" onclick="limparLog()">🗑️ Limpar Todo o Log</button>
            </div>

            <div style="margin-top: 30px;">
                <button class="collapsible">📋 Instruções para criar usuário e procedure no MySQL</button>
                <div class="collapsible-content">
                    <?php echo InstrucoesTexto('criar_usuario_procedure'); ?>
                </div>
                
                <button class="collapsible">🔧 Instruções para habilitar o General Log</button>
                <div class="collapsible-content">
                    <?php echo InstrucoesTexto('habilitar_log'); ?>
                </div>

                <?php
                // Show current MySQL configuration
                $selectString = "SHOW VARIABLES LIKE '%general_log%'";
                $stmt = $conn->prepare($selectString);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <button class="collapsible">⚙️ Configuração Atual do MySQL</button>
                <div class="collapsible-content">
                    <?php
                    $statusAtualGeneralLog = false;
                    foreach ($result as $value) {
                        if ($value['Variable_name'] == 'general_log' && $value['Value'] == 'ON') {
                            $statusAtualGeneralLog = true;
                        }
                        $varName = htmlspecialchars($value['Variable_name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $varValue = htmlspecialchars($value['Value'] ?? '', ENT_QUOTES, 'UTF-8');
                        echo "<div style='padding: 5px;'><strong>{$varName}:</strong> {$varValue}</div>";
                    }

                    if (!$statusAtualGeneralLog) {
                        echo "<br><button class='button button-success' onclick='loadContent(\"Habilitar\")'>✅ Habilitar General Log</button>";
                    } else {
                        echo "<br><button class='button button-danger' onclick='loadContent(\"Desabilitar\")'>❌ Desabilitar General Log</button>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Content for AJAX requests -->
        <div id="ajax-content">
            <?php
            $threadId = isset($_POST['thread_id']) ? $_POST['thread_id'] : '';

            if ($threadId === 'Limpar') {
                echo "<div class='alert alert-info'><h2>🗑️ Limpando o log...</h2></div>";
                $selectString = "TRUNCATE mysql.general_log";
                $stmt = $conn->prepare($selectString);
                $stmt->execute();
                echo "<div class='alert alert-success'><strong>✅ Pronto!</strong> Log limpo com sucesso.</div>";
                echo "<button class='button button-success' onclick='location.reload()'>🔄 Atualizar Página</button>";
            }
            elseif ($threadId === 'Habilitar') {
                echo "<div class='alert alert-info'><h2>✅ Habilitando o log...</h2></div>";
                $selectString = "CALL ToggleGeneralLog(TRUE)";
                $stmt = $conn->prepare($selectString);
                $stmt->execute();
                echo "<div class='alert alert-success'><strong>✅ Pronto!</strong> General Log habilitado.</div>";
                echo "<button class='button button-success' onclick='location.reload()'>🔄 Atualizar Página</button>";
            }
            elseif ($threadId === 'Desabilitar') {
                echo "<div class='alert alert-info'><h2>❌ Desabilitando o log...</h2></div>";
                $selectString = "CALL ToggleGeneralLog(FALSE)";
                $stmt = $conn->prepare($selectString);
                $stmt->execute();
                echo "<div class='alert alert-success'><strong>✅ Pronto!</strong> General Log desabilitado.</div>";
                echo "<button class='button button-success' onclick='location.reload()'>🔄 Atualizar Página</button>";
            }
            elseif (is_numeric($threadId)) {
                $threadId = (int)$threadId; // Sanitize: ensure it's an integer
                echo "<h1>📌 Thread #" . htmlspecialchars($threadId, ENT_QUOTES, 'UTF-8') . "</h1>";

                // Secure ORDER BY validation - whitelist allowed values
                $orderNum = isset($_POST['ordernum']) ? (int)$_POST['ordernum'] : 0;
                $allowedOrders = [
                    0 => 'total DESC, a.argument ASC',
                    1 => 'a.argument ASC',
                    2 => 'event_time ASC'
                ];
                $order = isset($allowedOrders[$orderNum]) ? $allowedOrders[$orderNum] : $allowedOrders[0];

                // Use parameterized query to prevent SQL injection
                $selectString = "
                    SELECT
                        COUNT(*) AS total,
                        DATE_FORMAT(event_time, '%d/%m/%Y %H:%i:%s') AS event_time_br,
                        a.*
                    FROM
                        `mysql`.`general_log` a
                    WHERE
                        a.thread_id = :thread_id
                    GROUP BY 
                        a.argument
                    ORDER BY
                        {$order}
                ";

                $stmt = $conn->prepare($selectString);
                $stmt->bindParam(':thread_id', $threadId, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<div style="margin-bottom: 15px;">';
                echo '<a href="#sql" class="button button-primary">📄 Ver SQL Completa</a>';
                echo '<button class="button button-warning" onclick="copyUserHost(\'' . htmlspecialchars($threadId, ENT_QUOTES, 'UTF-8') . '\')" title="Copiar identificação do usuário">👤 Copiar User Host</button>';
                echo '</div>';

                echo "<table>";
                echo "<thead><tr>";
                echo "<th>Tipo</th>";
                echo "<th><a href='javascript:void(0);' onclick='loadContent({$threadId}, 0)'>Total</a></th>";
                echo "<th><a href='javascript:void(0);' onclick='loadContent({$threadId}, 2)'>Hora</a></th>";
                echo "<th><a href='javascript:void(0);' onclick='loadContent({$threadId}, 1)'>Argumento</a></th>";
                echo "</tr></thead><tbody>";

                $sqlCompleto = [];
                $j = 0;
                foreach ($result as $value) {
                    if (in_array($value['command_type'], ['Quit', 'Connect'], true)) continue;
                    $j++;
                    $sqlCompleto[] = $value['argument'];
                    
                    // Secure output - escape all user data
                    $userTxt = htmlspecialchars($value['user_host'] ?? '', ENT_QUOTES, 'UTF-8');
                    $commandType = htmlspecialchars($value['command_type'] ?? '', ENT_QUOTES, 'UTF-8');
                    $total = (int)($value['total'] ?? 0);
                    $eventTime = htmlspecialchars($value['event_time_br'] ?? '', ENT_QUOTES, 'UTF-8');
                    $argument = htmlspecialchars($value['argument'] ?? '', ENT_QUOTES, 'UTF-8');
                    $jSafe = (int)$j;
                                       
                    echo "<tr title='User Host: {$userTxt}' class='thread-row' data-thread='{$threadId}' data-user='{$userTxt}'>";
                    echo "<td class='centralizado'>{$commandType}</td>";
                    echo "<td class='centralizado'>{$total}</td>";
                    echo "<td class='centralizado'>{$eventTime}</td>";
                    echo "<td><div style='max-height: 150px; overflow-y: auto;'>";
                    echo "<span class='copyTo' id='copy_{$jSafe}' title='Clique para copiar'>";
                    echo $argument;
                    echo "</span></div></td>";
                    echo "</tr>";
                }

                echo "</tbody></table>";

                if (count($sqlCompleto) > 0) {
                    echo '<div style="margin-top: 30px;">';
                    echo '<h2 id="sql">📄 SQL Completa</h2>';
                    echo '<textarea rows="20">' . htmlspecialchars(implode(";\n", $sqlCompleto) . ';') . '</textarea>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>

<?php
    // Close MySQL connection
    if ($conn) {
        $conn = null;
    }
?>

</body>
</html>
