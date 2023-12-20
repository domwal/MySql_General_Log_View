<?php
    header('Content-Type: text/html; charset=utf-8');

    // MySql Configuration
    // $mySqlServerName = "192.168.0.1:3306";
    $mySqlServerName = "localhost";
    $mySqlUserName   = "userlog";
    $mySqlPassword   = "yourPasswordHere";
    $mySqlDbName     = 'mysql';

    // show logs from mysql users
    $mysqlUserHost = [
        // '[root] @ localhost [::1]',
        // 'root[root] @ localhost [::1]',
    ];

    // not show logs from users
    $notMysqlUserHost = [
        '[root] @ localhost [::1]',
        'root[root] @ localhost [::1]',
        'root[root] @ localhost [127.0.0.1]'
    ];

    try {
        $conn = new PDO("mysql:host=$mySqlServerName;dbname={$mySqlDbName}", $mySqlUserName, $mySqlPassword);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // echo "Connected successfully";

        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit();
    }

?><!DOCTYPE html>
<html>
<head>
    <title>MySql General Log View</title>
    <meta charset="UTF-8"/>

    <style type="text/css">
        a {
            text-decoration: none;
        }
        * {
          box-sizing: border-box;
        }
        /* unvisited li class='button button-red'nk */
        a:link {
          color: darkgreen;
        }

        /* visited link */
        a:visited {
          color: darkgreen;
        }

        /* mouse over link */
        a:hover {
          color: red;
        }

        /* selected link */
        a:active {
          color: red;
        }

        body {
            background: #EFEFEF;
        }

        table {
            width: 100%;
        }

        .button, .button:link, .button:visited {
          border: none;
          color: white;
          padding: 10px 22px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          font-size: 16px;
          transition-duration: 0.4s;
          border-radius: 4px;
          margin: 2px;
        }
        .button-blue {
            background-color: #008CBA; /* Blue */
        }
        .button-red {
            background-color: #f44336; /* Red */
        }
        .button:hover {
          background-color: #4CAF50; /* Green */
          color: white;
          border-radius: 4px;
        }
        /* Create two equal columns that floats next to each other */
        .esquerda {
          float: left;
          width: 20%;
          padding: 10px;
          height: 300px; /* Should be removed. Only for demonstration */
        }
        .direita {
          float: left;
          width: 80%;
          padding: 10px;
          height: 300px; /* Should be removed. Only for demonstration */
        }
        /* Clear floats after the columns */
        .row:after {
          content: "";
          display: table;
          clear: both;
        }
        th {
            background-color: #C0C0C0;
            padding: 5px;
        }
        td {
            border:1px solid black;
            background-color: #FFF;
            padding: 2px;
        }
        iframe {
            width: 100%;
            height: 800px;
            border:1px solid black;
        }
        .copyTo {
            cursor: pointer;
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

        td.centralizado {
            text-align: center;
        }
    </style>
    <script type="text/javascript">

        function copyTextToClipboard(text) {
            var textArea = document.createElement("textarea");
            textArea.className = 'newTextArea';
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                var successful = document.execCommand('copy');
                var msg = successful ? 'Sim' : 'Nao';
                console.log('Texto copiado: ' + msg);
            } catch (err) {
               console.log('Erro ao copiar o texto');
            }
            document.body.removeChild(textArea);
        }

        function limparLog() {
            let isExecuted = confirm("Deseja Realmente Limpar Todo o General Log do Mysql?");

            if (isExecuted) {
                var elem = document.getElementById('clear-log-id');
                var url = elem.getAttribute('data-url');
                // alert(elem.getAttribute('data-url'));
                right_container.location = url;
                return true;
            }
            else {
                return false;
            }
        }

    </script>
</head>
<body>
<?php

    // *********************************************************************************
    if (empty($_GET['thread_id'])) :
        $whereCond = '';
        if (!empty($mysqlUserHost)) {
            $whereCond .= "\n                ";
            $whereCond .= "AND user_host IN ('" . implode("', '", $mysqlUserHost) . "')";
        }
        if (!empty($notMysqlUserHost)) {
            $whereCond .= "\n                ";
            $whereCond .= "AND user_host NOT IN ('" . implode("', '", $notMysqlUserHost) . "')";
        }

        $selectString = "
            SELECT
                thread_id,
                COUNT( * ) AS total,
                DATE_FORMAT(event_time, '%d/%m/%Y %H:%i:%s') AS event_time_br,
                user_host
            --  GROUP_CONCAT(user_host)
            --  GROUP_CONCAT( command_type ) 
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

        echo "<div class='row' style='padding-left: 10px;'>";
        echo "<a id='clear-log-id' href='javascript:;' onclick='limparLog();' data-url='".$_SERVER['REQUEST_URI']."?thread_id=Limpar' target='right_container' class='button button-red'>Limpar Log</a>";
        echo "<a href='javascript:parent.location.reload();' class='button button-blue'>Atualizar a Página</a>";
        echo "</div>";
        echo "<div class='row'>";
        echo "<div class='esquerda'>";
        echo "<table style='border: solid 1px black;'>";
        echo "
        <tr>
            <th>THREAD ID</th>
            <th>TOTAL</th>
            <th>HORA</th>
        </tr>";

        // set the resulting array to associative
        // $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($result as $value) {
            $count++;
            echo "<tr title='" . $value['user_host'] . "'>";
            echo "  <td style='width:80px;' class='centralizado'><a href='".$_SERVER['REQUEST_URI']."?thread_id=".(int)$value['thread_id']."&ordernum=2' target='right_container'>" . $value['thread_id']. "</a></td>";
            echo "  <td style='width:80px;' class='centralizado'>" . $value['total']. "</td>";
            echo "  <td style='width:150px;' class='centralizado'>" . $value['event_time_br']. "</td>";
            echo "</tr>";
        }

        if (!$count) {
            echo "<tr>";
            echo "  <td class='centralizado' colspan='3'>Nenhum Registro Encontrado</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "</div>";
        echo "<div class='direita'>";
        echo "<iframe name='right_container' src='".$_SERVER['REQUEST_URI']."?thread_id=Ajuda'></iframe>";
        echo "</div>";

        echo "</div>";
    // *********************************************************************************
    else :
        $threadId = (int)$_GET['thread_id'];
        if ($threadId) {
            echo "<h1># {$threadId}</h1>";
        }

        if (!$threadId && $_GET['thread_id'] == 'Limpar') {
            echo "<p><h2>Limpando o log ...</h2></p>";
            $selectString = "TRUNCATE mysql.general_log";
            $stmt = $conn->prepare($selectString);
            $stmt->execute();

            echo "<br>Pronto!";
            echo "<br><p><a href='javascript:parent.location.reload();' class='button button-blue'>Clique Aqui, para Atualizar a Página</a></p>";
            exit();
        }
        if (!$threadId && $_GET['thread_id'] == 'Ajuda') {
            $selectString = "SHOW VARIABLES LIKE '%general_log%'";
            $stmt = $conn->prepare($selectString);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<p>";
            echo "Instruções para Habilitar o General Log: <a href='javascript:;' onClick='document.getElementById(\"instrucoes-habilitar-log\").style.display = \"block\";'>Clique Aqui</a>";
            echo "</p>";
            echo "<div style='display: none; background-color: white; margin-left: 10px; padding: 10px; width: 500px;' id='instrucoes-habilitar-log'>";
            echo "- Editar o arquivo:";
            echo "<br> Windows: C:\\xampp\mysql\bin\my.ini";
            echo "<br> Linux: /etc/mysql/mariadb.conf.d/50-server.cnf";
            echo "<br>----------------------------------------";
            echo "<br>[mysqld]";
            echo "<br>... ";
            echo "<br>general_log=1";
            echo "<br>general_log_file=mysql_query.log";
            echo "<br>log_output = 'TABLE'";
            echo "<br>----------------------------------------";
            echo "<br>Ou em linha de comando";
            echo "<br>----------------------------------------";
            echo "<br>SET global general_log = 1;";
            echo "<br>SET global log_output = 'table';";
            echo "<br>SET global general_log_file = 'mysql_general_query.log';";
            echo "</div>";
            echo "<p>";

            echo "Instruções para criar usuario e procedure no MySql: <a href='javascript:;' onClick='document.getElementById(\"instrucoes-criar-usuario-procedure\").style.display = \"block\";'>Clique Aqui</a>";
            echo "<div style='display: none; background-color: white; margin-left: 10px; padding: 10px; width: 700px;' id='instrucoes-criar-usuario-procedure'>";
            echo "
            <br>-- <strong>Para criar usuario para acessar os logs e limpar o log, executar os seguintes comandos no mysql:</strong>
            <br>
            <br>CREATE USER 'userlog'@'%' IDENTIFIED BY 'yourPasswordHere';
            <br>GRANT SELECT ON mysql.general_log TO 'userlog'@'%';
            <br>
            <br>-- <strong>Para permitir limpar</strong>
            <br>
            <br>GRANT DROP ON mysql.general_log TO 'userlog'@'%';
            <br>FLUSH PRIVILEGES;
            <br>
            <br>-- <strong>Para poder alterar o general_log por aqui, é necessário criar essa procedure no mysql</strong>
            <br>
            <br>DELIMITER //
            <br>
            <br>CREATE PROCEDURE mysql.ToggleGeneralLog ( IN log_state BOOLEAN )
            <br>BEGIN
            <br>    IF log_state THEN
            <br>        SET GLOBAL general_log = 1;
            <br>        SET global log_output = 'table';
            <br>    ELSE 
            <br>        SET GLOBAL general_log = 0;
            <br>    END IF;
            <br>    
            <br>END // 
            <br>
            <br>DELIMITER;
            <br>
            <br>-- <strong>Permissão para o usuário executar a procedure acima</strong>
            <br>
            <br>GRANT EXECUTE ON PROCEDURE mysql.ToggleGeneralLog TO 'userlog'@'%';
            ";
            echo "</div>";
            echo "</p>";            
            echo "<br>----------------------------------------";
            echo "<br>- <strong>Configuração atual do MySql</strong>";
            echo "<br>----------------------------------------";

            // show variables like '%general_log%';
            $statusAtualGeneralLog = false;
            foreach ($result as $value) {
                if ($value['Variable_name'] == 'general_log' && $value['Value'] == 'ON') {
                    $statusAtualGeneralLog = true;
                }
                echo "<br>" . $value['Variable_name'] . " = " . $value['Value'];
            }

            // se o general_log estiver desabilitado, exibir um link para habilitar
            if (!$statusAtualGeneralLog) {
                echo "<br><br><a href='?thread_id=Habilitar' class='button button-blue'>Clique Aqui, para Habilitar o General Log</a>";
            }
            else {
                echo "<br><br><a href='?thread_id=Desabilitar' class='button button-red'>Clique Aqui, para Desabilitar o General Log</a>";
            }
            
            exit();
        }
        elseif (!$threadId && $_GET['thread_id'] == 'Habilitar') {
            echo "<p><h2>Habilitando o log ...</h2></p>";
            $selectString = "CALL ToggleGeneralLog(TRUE);";
            $stmt = $conn->prepare($selectString);
            $stmt->execute();

            echo "<br>Pronto!";
            echo "<br><p><a href='javascript:parent.location.reload();' class='button button-blue'>Clique Aqui, para Atualizar a Página</a></p>";
            exit();
        }
        elseif (!$threadId && $_GET['thread_id'] == 'Desabilitar') {
            echo "<p><h2>Desabilitando o log ...</h2></p>";
            $selectString = "CALL ToggleGeneralLog(FALSE)";
            $stmt = $conn->prepare($selectString);
            $stmt->execute();

            echo "<br>Pronto!";
            echo "<br><p><a href='javascript:parent.location.reload();' class='button button-blue'>Clique Aqui, para Atualizar a Página</a></p>";
            exit();
        }
        elseif (!$threadId) {
            echo "Erro!";
            exit();
        }


        $order = 'total DESC, a.argument ASC';
        $orderNum = (int)@$_GET['ordernum'];
        if (in_array($orderNum, [1,2])) {
            
            if ($orderNum == 1) {
                $order = 'a.argument ASC';
            }
            elseif ($orderNum == 2) {
                $order = 'event_time ASC';
            }
        }

        $selectString = "
            SELECT
                COUNT(*) AS total,
                DATE_FORMAT(event_time, '%d/%m/%Y %H:%i:%s') AS event_time_br,
                a.*
            FROM
                `mysql`.`general_log` a
            WHERE
                a.thread_id = {$threadId}
            GROUP BY 
                a.argument
            ORDER BY
                {$order}
        ";

        $stmt = $conn->prepare($selectString);
        $stmt->execute();

        $urlUri = $_SERVER['REQUEST_URI'];
        if ($posUrlNum = (int) strpos($urlUri, '&')) {
            $urlUri = substr($urlUri, 0, $posUrlNum);
        }

        echo '<hr>';
        echo '<a href="'. $_SERVER['REQUEST_URI'] .'#sql" class="button button-red" style="float:right;">Ver a Sql Completa</a>';

        echo "<table style='border: solid 1px black;'>";
        echo "
        <tr>
            <th>-</th>
            <th><a href='{$urlUri}&ordernum=0'>TOTAL</a></th>
            <th><a href='{$urlUri}&ordernum=2'>HORA</a></th>
            <th><a href='{$urlUri}&ordernum=1'>ARGUMENTO</a></th>
        </tr>";

        // set the resulting array to associative
        // $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlCompleto = [];
        $j=0;
        foreach ($result as $value) {
            if (in_array($value['command_type'], ['Quit', 'Connect'])) continue;
            $j++;
            $sqlCompleto[] = ($value['argument']);
            echo "<tr>";
            echo "  <td style='width:80px;' class='centralizado'>" . $value['command_type']. "</td>";
            echo "  <td style='width:80px;' class='centralizado'>" . $value['total']. "</td>";
            echo "  <td style='width:150px;' class='centralizado'>" . $value['event_time_br']. "</td>";
            echo "  <td><span class='copyTo' id='{$j}' title='Clique para copiar o texto'>" . str_replace(['<', '>'], ['&lt;', '&gt;'], $value['argument']) . "</span></td>";
            echo "</tr>";
        }
        echo "</table>";


        echo '<hr>';
        echo '<h1>Sql Completa</h1>';
        echo '<a name="sql"></a>';
        echo '<p>';
        echo '<textarea rows="20" style="width:100%;">' . implode(";\n", $sqlCompleto) . ';</textarea>';
        echo '</p>';
        echo '<hr>';
        echo '<a href="javascript:window.scrollTo(0, 0);" class="button button-red" style="float:right;">Voltar Topo</a>';
    endif;
    // *********************************************************************************
?>




<?php
    // close mysql connection
    $conn = null;
?>


<script type="text/javascript">
    var el = document.querySelectorAll(".copyTo");
    for(var i =0; i < el.length; i++) {
        el[i].onclick = function() {
            id = this.id;
            var node = document.getElementById(id);
            textContent = node.innerHTML;
            textContent = textContent.replace(/&lt;/g, '<').replace(/&gt;/g, '>');
            copyTextToClipboard(textContent);
        };
    }
</script>
</body>
</html>
