<?php

// Carregar configurações de ambiente
require_once __DIR__ . '/config.php';

session_start();
$clienteCriadoComSucesso = false;
$clienteBuscadoComSucesso = false;
$clienteInfo = "";
$cobrancasEncontradas = false;
$cobrancas = [];
$erroBuscaCliente = "";
$erroBuscaCobrancas = "";

// Carregar token da API Asaas das variáveis de ambiente
$token = env('ASAAS_TOKEN', '');

// Carregar IDs das carteiras dos vendedores das variáveis de ambiente
$vendedores = [
    "Carolina" => env('VENDEDOR_CAROLINA_WALLET_ID', ''),
    "Jennifer" => env('VENDEDOR_JENNIFER_WALLET_ID', ''),
    "Jassera" => env('VENDEDOR_JASSERA_WALLET_ID', ''),
];

// Verificar se as variáveis de ambiente foram carregadas
if (empty($token)) {
    die('Erro: Token da API Asaas não configurado. Verifique o arquivo .env');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $acao = $_POST['acao'];
    $curl = curl_init();

    switch ($acao) {
        case 'buscar_cliente':
            $cpfCnpjBusca = trim($_POST['cpfCnpjBusca'] ?? '');
            
            if (empty($cpfCnpjBusca)) {
                $erroBuscaCliente = "Por favor, informe o CPF/CNPJ para buscar.";
                break;
            }
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.asaas.com/v3/customers?cpfCnpj=" . urlencode($cpfCnpjBusca),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "access_token: " . $token,
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                $erroBuscaCliente = "Erro de conexão: " . $err;
            } elseif ($httpCode != 200) {
                $responseData = json_decode($response, true);
                $erroBuscaCliente = "Erro na API: " . ($responseData['errors'][0]['description'] ?? "Código HTTP $httpCode");
            } else {
                $responseData = json_decode($response, true);
                if (!empty($responseData['data']) && count($responseData['data']) > 0) {
                    $clienteBuscadoComSucesso = true;
                    $clienteInfo = $responseData['data'][0];
                    $_SESSION['clienteId'] = $responseData['data'][0]['id'];
                } else {
                    $erroBuscaCliente = "Cliente não encontrado com o CPF/CNPJ informado.";
                }
            }
            break;

        case 'buscar_cobrancas':
            $customerIdParaCobrancas = trim($_POST['customerIdCobrancas'] ?? '');
            
            if (empty($customerIdParaCobrancas)) {
                $customerIdParaCobrancas = isset($_SESSION['clienteId']) ? $_SESSION['clienteId'] : '';
            }
            
            if (!empty($customerIdParaCobrancas)) {
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.asaas.com/v3/payments?customer=" . urlencode($customerIdParaCobrancas),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => [
                        "accept: application/json",
                        "access_token: " . $token,
                    ],
                ]);

                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    $erroBuscaCobrancas = "Erro de conexão: " . $err;
                } elseif ($httpCode != 200) {
                    $responseData = json_decode($response, true);
                    $erroBuscaCobrancas = "Erro na API: " . ($responseData['errors'][0]['description'] ?? "Código HTTP $httpCode");
                } else {
                    $responseData = json_decode($response, true);
                    $cobrancasEncontradas = true;
                    $cobrancas = !empty($responseData['data']) ? $responseData['data'] : [];
                }
            } else {
                $erroBuscaCobrancas = "Por favor, informe o ID do cliente.";
                curl_close($curl);
            }
            break;

        case 'criar_cliente':
        case 'criar_cobranca':
            // Executar a requisição para criar_cliente e criar_cobranca
            if ($acao == 'criar_cliente') {
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.asaas.com/v3/customers",
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode([
                        'name' => $_POST['name'],
                        'cpfCnpj' => $_POST['cpfCnpj'],
                        'email' => $_POST['email'],
                        'mobilePhone' => $_POST['mobilePhone'],
                        'address' => $_POST['address'],
                        'province' => $_POST['province'],
                        'postalCode' => $_POST['postalCode'],
                        'addressNumber' => $_POST['addressNumber'],
                    ]),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json",
                        "access_token: " . $token,
                    ],
                ]);
            } elseif ($acao == 'criar_cobranca') {
                $walletIdDestinatario = $vendedores[$_POST['vendedor']];
                
                // Verificar se um ID de cliente existente foi fornecido
                $customerID = !empty($_POST['existingCustomerId']) ? $_POST['existingCustomerId'] : $_SESSION['clienteId'];

                // Definindo o percentual de comissão
                $percentualComissao = ($_POST['vendedor'] === 'Carolina') ? 25 : 15; // 25% se for Carolina, 15% para outros
                
                // Preparar os dados do pagamento
                $paymentData = [
                    'customer' => $customerID, // Usa o ID existente ou da sessão
                    'billingType' => $_POST['tipoPagamento'],
                    'dueDate' => $_POST['dueDate'],
                    'value' => $_POST['valor'],
                    'split' => [
                        [
                            'walletId' => $walletIdDestinatario,
                            'percentualValue' => $percentualComissao,
                        ],
                    ],
                ];
                # Para crédito recorrente, adicionar o campo 'installmentCount' e 'installmentValue'
                 // Se o tipo de pagamento selecionado é Cartão de Crédito
                if ($_POST['tipoPagamento'] === 'CREDIT_CARD' && !empty($_POST['installmentCount'])) {
                    $paymentData['installmentCount'] = $_POST['installmentCount'];
                    $paymentData['installmentValue'] = $_POST['valor'] / $_POST['installmentCount'];

                    // Adicionando creditCardHolderInfo (ajustar conforme os campos do formulário)
                    $paymentData['creditCardHolderInfo'] = [
                        "name" => $_POST['cardName'] ?? 'Nome padrão',
                        "email" => $_POST['cardEmail'] ?? 'email@padrao.com',
                        "cpfCnpj" => $_POST['cardCpfCnpj'] ?? '000.000.000-00',
                        "postalCode" => $_POST['cardPostalCode'] ?? '00000-000',
                        "addressNumber" => $_POST['cardAddressNumber'] ?? '0',
                        "addressComplement" => $_POST['cardAddressComplement'] ?? '' // Campo opcional
                    ];
                }

                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.asaas.com/v3/payments",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => json_encode($paymentData),
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json",
                        "access_token: " . $token,
                    ],
                ]);
            }
            
            // Executar a requisição
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if (!$err) {
                $responseData = json_decode($response, true);
                if (isset($responseData['id'])) {
                    if ($acao == 'criar_cliente') {
                        $clienteCriadoComSucesso = true;
                        $_SESSION['clienteId'] = $responseData['id'];
                    } elseif ($acao == 'criar_cobranca') {
                        $_SESSION['cobrancaId'] = $responseData['id'];
                        echo "<p>Cobrança criada com sucesso! ID do Pagamento: " . htmlspecialchars($responseData['id']) . "</p>";
                    }
                }
            } else {
                echo "Erro ao processar a solicitação: $err";
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assessoria para Visto Americano</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fde2e4;
            color: #362c28;
            text-align: center;
            background-image: url('https://static.vecteezy.com/ti/fotos-gratis/p1/38813080-ai-gerado-lindo-natureza-fundo-apresentando-uma-solitario-montanha-pico-contra-uma-rosa-roxa-gradiente-ceu-gratis-foto.jpeg');
            background-size: cover;
        }
        h2 {
            color: #913c55;
        }
        ul {
            list-style-type: none;
            padding: 0;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        li {
            background-color: #ee7596;
            padding: 10px 20px;
            border-radius: 10px;
            transition: background-color 0.3s;
        }
        li a {
            text-decoration: none;
            color: white;
        }
        li:hover {
            background-color: #d94f70;
        }
        form {
            margin: 20px auto;
            text-align: left;
            display: inline-block;
        }
        input[type=text], input[type=email], input[type=date], select {
            padding: 7px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            width: calc(100% - 16px);
            box-sizing: border-box;
        }
        .button {
            background-color: #ee7596;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            display: inline-block;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
        }
        .button:hover {
            background-color: #d94f70;
        }
    </style>
</head>
<body>
    <h2>Sistema de split automático Visa Advisory</h2>
    <p>"Mesmo o caminho mais longo começa com um simples passo."</p>
    <ul>
        <li><a href="#" onclick="exibirConteudo('criar_cliente'); return false;">Criar Cliente</a></li>
        <li><a href="#" onclick="exibirConteudo('split_pagamento'); return false;">Split de Pagamento</a></li>
        <li><a href="#" onclick="exibirConteudo('buscar_cliente'); return false;">Buscar Cliente por CPF/CNPJ</a></li>
        <li><a href="#" onclick="exibirConteudo('ver_cobrancas'); return false;">Ver Cobranças do Cliente</a></li>
    </ul>

    <div id="criar_cliente" style="display:none;">
    <?php if ($clienteCriadoComSucesso): ?>
        <p>Cliente criado com sucesso!</p>
    <?php else: ?>
        <form action="" method="post">
            Nome: <input type="text" name="name"><br>
            CPF/CNPJ: <input type="text" name="cpfCnpj"><br>
            E-mail: <input type="email" name="email"><br>
            Celular: <input type="text" name="mobilePhone"><br>
            Rua: <input type="text" name="address"><br>
            Bairro: <input type="text" name="province"><br>
            CEP: <input type="text" name="postalCode"><br>
            Número do endereço: <input type="text" name="addressNumber"><br>
            <input type="hidden" name="acao" value="criar_cliente">
            <input type="submit" value="Criar cliente">
        </form>
    <?php endif; ?>
    </div>

    <div id="split_pagamento" style="display:none;">
    <?php if (isset($_SESSION['clienteId']) || $clienteBuscadoComSucesso): ?>
        <form action="" method="post">
            <label>Id do cliente:</label>
            <input type="text" name="existingCustomerId" value="<?php echo $clienteBuscadoComSucesso ? htmlspecialchars($clienteInfo['id']) : ''; ?>"><br>
            <label>Valor:</label>
            <input type="text" name="valor" required><br>
            <label>Data de Vencimento:</label>
            <input type="date" name="dueDate" required><br>
            <label>Tipo de Pagamento:</label>
            <select name="tipoPagamento" onchange="toggleCreditCardInfo()">
                <option value="PIX">Pix</option>
                <option value="CREDIT_CARD">Cartão de Crédito</option>
            </select><br>
            <label>Vendedor:</label>
            <select name="vendedor">
                <option value="Carolina">Carolina</option>
                <option value="Jennifer">Jennifer</option>
                <option value="Jassera">Jassera</option>
            </select><br>
            
            <div id="creditCardInfo" style="display: none;">
                <label>Número de Parcelas:</label>
                <input type="number" name="installmentCount" min="1" max="12"><br>
                <label>Nome no Cartão:</label>
                <input type="text" name="cardName"><br>
                <label>Email do titular:</label>
                <input type="email" name="cardEmail"><br>
                <label>CPF/CNPJ do titular:</label>
                <input type="text" name="cardCpfCnpj"><br>
                <label>CEP:</label>
                <input type="text" name="cardPostalCode"><br>
                <label>Número da Residência:</label>
                <input type="text" name="cardAddressNumber"><br>
                <label>Complemento (opcional):</label>
                <input type="text" name="cardAddressComplement"><br>
            </div>

            <input type="hidden" name="acao" value="criar_cobranca">
            <input type="submit" value="Criar Cobrança">
        </form>
    <?php else: ?>
        <p>Por favor, crie um cliente primeiro ou busque um cliente existente.</p>
    <?php endif; ?> 
    </div>

    <div id="buscar_cliente" style="display:none;">
    <?php if ($clienteBuscadoComSucesso): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px auto; max-width: 500px;">
            <h3 style="color: #155724; margin-top: 0;">✅ Cliente encontrado!</h3>
            <p><strong>ID do Cliente:</strong> <?php echo htmlspecialchars($clienteInfo['id']); ?></p>
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($clienteInfo['name'] ?? ''); ?></p>
            <p><strong>CPF/CNPJ:</strong> <?php echo htmlspecialchars($clienteInfo['cpfCnpj'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($clienteInfo['email'] ?? ''); ?></p>
        </div>
    <?php elseif (!empty($erroBuscaCliente)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px auto; max-width: 500px;">
            <h3 style="color: #721c24; margin-top: 0;">❌ Erro ao buscar cliente</h3>
            <p><?php echo htmlspecialchars($erroBuscaCliente); ?></p>
        </div>
    <?php endif; ?>
    <form action="" method="post" style="margin-top: 20px;">
        <label>CPF/CNPJ:</label>
        <input type="text" name="cpfCnpjBusca" value="<?php echo isset($_POST['cpfCnpjBusca']) ? htmlspecialchars($_POST['cpfCnpjBusca']) : ''; ?>" required><br>
        <input type="hidden" name="acao" value="buscar_cliente">
        <input type="submit" value="Buscar Cliente" class="button">
    </form>
    </div>

    <div id="ver_cobrancas" style="display:none;">
    <?php if (!empty($erroBuscaCobrancas)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px auto; max-width: 500px;">
            <h3 style="color: #721c24; margin-top: 0;">❌ Erro ao buscar cobranças</h3>
            <p><?php echo htmlspecialchars($erroBuscaCobrancas); ?></p>
        </div>
    <?php elseif ($cobrancasEncontradas): ?>
        <h3>Cobranças encontradas:</h3>
        <?php if (empty($cobrancas)): ?>
            <div style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px auto; max-width: 500px;">
                <p><strong>⚠️ Nenhuma cobrança encontrada para este cliente.</strong></p>
                <p>O cliente pode não ter cobranças cadastradas ainda.</p>
            </div>
        <?php else: ?>
            <table style="margin: 20px auto; border-collapse: collapse; width: 80%;">
                <tr style="background-color: #913c55; color: white;">
                    <th style="padding: 10px; border: 1px solid #ddd;">ID</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Valor</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Vencimento</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Status</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Tipo</th>
                </tr>
                <?php foreach ($cobrancas as $cobranca): ?>
                    <tr style="background-color: white;">
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($cobranca['id'] ?? ''); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">R$ <?php echo number_format($cobranca['value'] ?? 0, 2, ',', '.'); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo isset($cobranca['dueDate']) ? date('d/m/Y', strtotime($cobranca['dueDate'])) : ''; ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($cobranca['status'] ?? ''); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($cobranca['billingType'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p style="text-align: center; margin-top: 10px;"><strong>Total de cobranças: <?php echo count($cobrancas); ?></strong></p>
        <?php endif; ?>
    <?php endif; ?>
    <form action="" method="post" style="margin-top: 20px;">
        <label>ID do Cliente:</label>
        <input type="text" name="customerIdCobrancas" value="<?php echo isset($_POST['customerIdCobrancas']) ? htmlspecialchars($_POST['customerIdCobrancas']) : (isset($_SESSION['clienteId']) ? htmlspecialchars($_SESSION['clienteId']) : ''); ?>" required><br>
        <input type="hidden" name="acao" value="buscar_cobrancas">
        <input type="submit" value="Buscar Cobranças" class="button">
    </form>
    </div>
    <script>
        function toggleCreditCardInfo() {
            var selectTipoPagamento = document.querySelector("select[name='tipoPagamento']");
            var creditCardInfo = document.getElementById('creditCardInfo');
            
            if (selectTipoPagamento && creditCardInfo) {
                creditCardInfo.style.display = selectTipoPagamento.value == 'CREDIT_CARD' ? 'block' : 'none';
            }
        }

        function exibirConteudo(id) {
            var todasAsAbas = document.querySelectorAll('div[id]');
            for (var i = 0; i < todasAsAbas.length; i++) {
                todasAsAbas[i].style.display = 'none';
            }
            var abaAtual = document.getElementById(id);
            if (abaAtual) {
                abaAtual.style.display = 'block';
                sessionStorage.setItem('abaAtiva', id);
                
                // Se a aba for split_pagamento, configurar o event listener do select
                if (id === 'split_pagamento') {
                    var selectTipoPagamento = document.querySelector("select[name='tipoPagamento']");
                    if (selectTipoPagamento) {
                        selectTipoPagamento.addEventListener('change', toggleCreditCardInfo);
                        toggleCreditCardInfo(); // Atualizar estado inicial
                    }
                }
            }
        }

        window.onload = function() {
            var abaAtiva = sessionStorage.getItem('abaAtiva');
            if (!abaAtiva) {
                abaAtiva = 'criar_cliente'; // Faz 'Criar Cliente' ser a aba padrão inicial
            }
            exibirConteudo(abaAtiva);
        };
    </script>
</body>
</html>