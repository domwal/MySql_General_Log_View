<?php
    header('Content-Type: text/html; charset=utf-8');

    // MySql Configuration
    $mySqlServerName = "localhost";
    $mySqlUserName   = "root";
    $mySqlPassword   = "";
    $mySqlDbName     = 'mysql';

    // show logs from mysql users
    $mysqlUserHost = [
        // '[root] @ localhost [::1]',
        // 'root[root] @ localhost [::1]',
    ];

    // no show logs from users
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

    <style type="text/css">
        a {
            text-decoration: none;
        }
        * {
          box-sizing: border-box;
        }
        /* unvisited li class='button'nk */
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
          background-color: #f44336; /* Red */
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
        // echo $selectString;

        $stmt = $conn->prepare($selectString);
        $stmt->execute();

        echo "<div class='row' style='padding-left: 10px;'>";
        echo "<a id='clear-log-id' href='javascript:;' onclick='limparLog();' data-url='".$_SERVER['REQUEST_URI']."?thread_id=Limpar' target='right_container' class='button'>Limpar Log</a>";
        echo "<a href='javascript:parent.location.reload();' class='button'>Atualizar a Página</a>";
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
            echo "  <td style='width:80px;' class='centralizado'><a href='".$_SERVER['REQUEST_URI']."?thread_id=".(int)$value['thread_id']."' target='right_container'>" . $value['thread_id']. "</a></td>";
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
            echo "<br><p><a href='javascript:parent.location.reload();' class='button'>Clique Aqui, para Atualizar a Página</a></p>";
            exit();
        }
        if (!$threadId && $_GET['thread_id'] == 'Ajuda') {
            echo "<p>";
            echo "<h2>Para Habilitar o General Log:</h2>";
            echo "</p>";
            echo "<br>- Editar o C:\\xampp\mysql\bin\my.ini";
            echo "<br>----------------------------------------";
            echo "<br>[mysqld]";
            echo "<br>... ";
            echo "<br>general_log";
            echo "<br>general_log_file=C:/xampp/mysql/log/mysql_query.log";
            echo "<br>log_output = 'TABLE'";
            echo "<br>----------------------------------------";
            echo "<br>Ou em linha de comando";
            echo "<br>----------------------------------------";
            echo "<br>SET global general_log = 1;";
            echo "<br>SET global log_output = 'table';";
            echo "<br>----------------------------------------";
            exit();
        }
        elseif (!$threadId) {
            echo "Erro!";
            exit();
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
                total DESC, a.argument ASC
        ";

        $stmt = $conn->prepare($selectString);
        $stmt->execute();

        echo "<table style='border: solid 1px black;'>";
        echo "
        <tr>
            <th>-</th>
            <th>TOTAL</th>
            <th>HORA</th>
            <th>ARGUMENTO</th>
        </tr>";

        // set the resulting array to associative
        // $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $j=0;
        foreach ($result as $value) {
            if (in_array($value['command_type'], ['Quit', 'Connect'])) continue;
            $j++;
            echo "<tr>";
            echo "  <td style='width:80px;' class='centralizado'>" . $value['command_type']. "</td>";
            echo "  <td style='width:80px;' class='centralizado'>" . $value['total']. "</td>";
            echo "  <td style='width:150px;' class='centralizado'>" . $value['event_time_br']. "</td>";
            echo "  <td><span class='copyTo' id='{$j}' title='Clique para copiar o texto'>" . utf8_encode($value['argument']) . "</span></td>";
            echo "</tr>";
        }
        echo "</table>";
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
