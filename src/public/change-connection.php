<?php
// Melhora a interface do usuário para a troca de conexão de banco de dados.
// Adiciona um layout mais limpo, feedback visual aprimorado e melhor organização do código.

define('BASEPATH', '');
const ENVIRONMENT = 'homolog';
include_once 'application/config/database.php';
ob_start();

// Impede o acesso direto se a configuração padrão do banco de dados não estiver definida.
if (isset($db['default']) && $db['default']){
    exit('Unauthorized access');
}

// Processa a alteração de senha.
if (isset($_POST['change_password'])) {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    $active_group = $_COOKIE['connection_name'] ?? 'default';

    if (isset($db[$active_group])) {
        $db_config = $db[$active_group];
        $mysqli = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);

        if ($mysqli->connect_error) {
            $message = "Erro de conexão com o banco de dados: " . $mysqli->connect_error;
            $message_type = 'danger';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET last_change_password = now(), password = ? WHERE email = ?");
            $stmt->bind_param('ss', $hashed_password, $email);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "Senha alterada com sucesso para o usuário '<strong>{$email}</strong>'!";
                    $message_type = 'success';
                } else {
                    $message = "Nenhum usuário encontrado com o e-mail '<strong>{$email}</strong>'.";
                    $message_type = 'warning';
                }
            } else {
                $message = "Erro ao alterar a senha: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
            $mysqli->close();
        }
    } else {
        $message = "Configuração de banco de dados não encontrada para a conexão ativa.";
        $message_type = 'danger';
    }
}

// Função para encontrar a linha que contém uma string específica em um arquivo.
function getLineWithString($fileName, $str) {
    $lines = file($fileName);
    foreach ($lines as $lineNumber => $line) {
        if (strpos($line, $str) !== false) {
            return $line;
        }
    }
    return -1;
}

$message = '';
$message_type = '';

// Processa o formulário quando enviado.
if ($_POST && isset($_POST['name'])){
    $active_group = $_POST['name'];
    setcookie('connection_name', $active_group, time() + (10 * 365 * 24 * 60 * 60), "/");

    if (isset($_POST['batches'])){
        $database_file = 'application/config/database.php';
        $data = file_get_contents($database_file, true);
        $lineToSearch = getLineWithString($database_file, '$active_group =');

        if ($lineToSearch !== -1) {
            $replace = '$active_group = isset($_COOKIE["connection_name"]) && $_COOKIE["connection_name"] ? $_COOKIE["connection_name"] : \''.$active_group.'\';'."
";
            $data = str_replace($lineToSearch, $replace, $data);
            if (file_put_contents($database_file, $data)) {
                $message = "Conexão do banco de dados alterada para '<strong>{$active_group}</strong>' com sucesso, incluindo batches!";
                $message_type = 'success';
            } else {
                $message = "Erro ao gravar no arquivo de configuração do banco de dados.";
                $message_type = 'danger';
            }
        } else {
            $message = "A linha de configuração 'active_group' não foi encontrada.";
            $message_type = 'warning';
        }
    } else {
        $message = "Conexão do banco de dados alterada para '<strong>{$active_group}</strong>' com sucesso! (Batches não foram alterados)";
        $message_type = 'success';
    }
} else {
    // Se não for um POST, determina o grupo ativo.
    // Primeiro, lê a configuração do banco de dados para encontrar o grupo padrão.
    $database_config = file_get_contents('application/config/database.php');
    // Usa regex para extrair o nome do grupo padrão da linha $active_group.
    preg_match('/$active_group\s*=\s*.*?\s*:\s*\'(.*?)\'/', $database_config, $matches);
    $default_group = $matches[1] ?? 'default'; // Usa 'default' como fallback.

    // O grupo ativo é o que está no cookie, ou o padrão se o cookie não existir.
    $active_group = $_COOKIE['connection_name'] ?? $default_group;
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Conexão de Banco de Dados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">Gerenciador de Conexão</h4>
            <div>
                <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                    Alterar Senha
                </button>
                <a href="<?php echo str_replace('change-connection.php', '', $_SERVER['REQUEST_URI']); ?>" class="btn btn-outline-primary">Abrir Site</a>
            </div>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form role="form" action="" method="post">
                <div class="mb-3">
                    <label for="connection-select" class="form-label">Selecione o Banco de Dados:</label>
                    <select name="name" id="connection-select" class="form-select">
                        <?php
                        foreach ($db as $connectionName => $options){
                            $selected = ($active_group == $connectionName) ? 'selected' : '';
                            echo "<option $selected value='$connectionName'>".htmlspecialchars($connectionName)."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" value="1" id="update-batches" name="batches" checked>
                    <label class="form-check-label" for="update-batches">
                        Alterar para Batches também?
                    </label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Alterar Conexão</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-muted">
            Conexão atual: <strong><?php echo htmlspecialchars($active_group); ?></strong>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="changePasswordModalLabel">Alterar Senha</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="post">
        <div class="modal-body">
          <div class="mb-3">
            <label for="email" class="form-label">Email do Usuário</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="new-password" class="form-label">Nova Senha</label>
            <input type="password" class="form-control" id="new-password" name="new_password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="submit" name="change_password" class="btn btn-primary">Salvar Alterações</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>