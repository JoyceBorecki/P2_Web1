<?php
$host = '200.236.3.126';
$user = 'root';
$password = 'example';
$database = 'world';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$itens_por_pagina = isset($_GET['itens_por_pagina']) ? $_GET['itens_por_pagina'] : 100;
$pagina_atual = isset($_GET['pagina']) ? $_GET['pagina'] : 1;
$offset = ($pagina_atual - 1) * $itens_por_pagina;

$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'Name';
$ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'ASC';

$termo_busca = isset($_GET['termo_busca']) ? $_GET['termo_busca'] : '';
$buscando_pais = !empty($termo_busca);

$campos_permitidos = ['Name', 'Population', 'Capital', 'Languages'];
if (!in_array($ordenar_por, $campos_permitidos)) {
    $ordenar_por = 'Name';
}

if ($buscando_pais) {
    $sql = "SELECT Country.Code, Country.Name, Country.Population, City.Name as Capital, GROUP_CONCAT(CountryLanguage.Language) as Languages
            FROM Country
            LEFT JOIN City ON Country.Capital = City.ID
            LEFT JOIN CountryLanguage ON Country.Code = CountryLanguage.CountryCode
            WHERE Country.Name LIKE '%$termo_busca%' OR City.Name LIKE '%$termo_busca%'
            GROUP BY Country.Code, Country.Name, Country.Population, City.Name
            ORDER BY $ordenar_por $ordenacao
            LIMIT $itens_por_pagina OFFSET $offset";

    echo "<h2>Resultados da busca por '$termo_busca'</h2>";
} else {
    $sql = "SELECT Country.Code, Country.Name, Country.Population, City.Name as Capital, GROUP_CONCAT(CountryLanguage.Language) as Languages
            FROM Country
            LEFT JOIN City ON Country.Capital = City.ID
            LEFT JOIN CountryLanguage ON Country.Code = CountryLanguage.CountryCode
            GROUP BY Country.Code, Country.Name, Country.Population, City.Name
            ORDER BY $ordenar_por $ordenacao
            LIMIT $itens_por_pagina OFFSET $offset";
}

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th><a href='?ordenar_por=Name&ordenacao=" . ($ordenar_por == 'Name' && $ordenacao == 'ASC' ? 'DESC' : 'ASC') . "'>País</a></th>
              <th><a href='?ordenar_por=Capital&ordenacao=" . ($ordenar_por == 'Capital' && $ordenacao == 'ASC' ? 'DESC' : 'ASC') . "'>Capital</a></th>
              <th><a href='?ordenar_por=Population&ordenacao=" . ($ordenar_por == 'Population' && $ordenacao == 'ASC' ? 'DESC' : 'ASC') . "'>População do País</a></th>
              <th><a href='?ordenar_por=Languages&ordenacao=" . ($ordenar_por == 'Languages' && $ordenacao == 'ASC' ? 'DESC' : 'ASC') . "'>Línguas</a></th></tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Name']}</td><td>{$row['Capital']}</td><td>{$row['Population']}</td><td>{$row['Languages']}</td></tr>";
    }

    echo "</table>";
} else {
    echo "Nenhum resultado encontrado.";
}

echo "<br><br>";
echo "<form action='' method='GET'>";
echo "Buscar por nome de país ou capital: <input type='text' name='termo_busca' value='$termo_busca'>";
echo " Itens por página: 
    <select name='itens_por_pagina'>
        <option value='10' " . ($itens_por_pagina == 10 ? 'selected' : '') . ">10</option>
        <option value='50' " . ($itens_por_pagina == 50 ? 'selected' : '') . ">50</option>
        <option value='100' " . ($itens_por_pagina == 100 ? 'selected' : '') . ">100</option>
        <option value='239' " . ($itens_por_pagina == 239 ? 'selected' : '') . ">239 (Todos)</option>
    </select>";
echo "<input type='hidden' name='pagina' value='1'>";
echo "<input type='hidden' name='ordenar_por' value='$ordenar_por'>";
echo "<input type='hidden' name='ordenacao' value='$ordenacao'>";
echo "<input type='submit' value='Buscar'>";
echo "</form>";

if ($buscando_pais) {
    echo "<br><br>";
    echo "<a href='?itens_por_pagina=$itens_por_pagina&pagina=1&ordenar_por=$ordenar_por&ordenacao=$ordenacao'>Voltar à tabela completa</a>";
}

$sql_count = "SELECT COUNT(DISTINCT Country.Code) as total
              FROM Country
              LEFT JOIN City ON Country.Capital = City.ID
              LEFT JOIN CountryLanguage ON Country.Code = CountryLanguage.CountryCode
              WHERE Country.Name LIKE '%$termo_busca%' OR City.Name LIKE '%$termo_busca%'";
$result_count = $conn->query($sql_count);
$row_count = $result_count->fetch_assoc();
$total_itens = $row_count['total'];
$total_paginas = ceil($total_itens / $itens_por_pagina);

echo "<br><br>";
echo "Página: ";
for ($i = 1; $i <= $total_paginas; $i++) {
    echo "<a href='?pagina=$i&itens_por_pagina=$itens_por_pagina&ordenar_por=$ordenar_por&ordenacao=$ordenacao&termo_busca=$termo_busca'>$i</a> ";
}

$conn->close();
?>