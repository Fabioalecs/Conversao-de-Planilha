<?php

if ($argc >= 2) {
    $filePath = $argv[1];
}

echo "Iniciando script" . PHP_EOL;

$header = [];
$informations = [];
$row = 0;

echo "Abrindo arquivo csv" . PHP_EOL;
if(($handle = fopen($filePath ?? "Pasta1.csv", "r")) !== false) {
    echo "Arquivo aberto com sucesso" . PHP_EOL;
    while (($data = fgetcsv($handle, 10000, ";")) !== false) {
        if (0 === $row) {
            echo "Parseando headers" . PHP_EOL;
            foreach ($data as $head) {
                $header[] = $head;
            }

            $row++;
            echo "Fim do parser header" . PHP_EOL;
            continue;
        }

        if ($row === 1) echo "Parseando linhas" . PHP_EOL;

        $information = [];

        foreach ($header as $index => $head) {
            $information[$head] = $data[$index];
        }

        $informations[] = $information;

        $row++;
    }
    echo "Fim do parser linhas" . PHP_EOL;
}

fclose($handle);

echo "Headers:" . PHP_EOL;
foreach ($header as $head) {
    echo "$head" . PHP_EOL;
    echo "==================" . PHP_EOL;
}

$weekly = "SEMANAL";
$fifteenly = "QUINZENAL";

$filterByWeek = function ($item) use ($weekly) {
    return ($item["Frequência de visitas"] === $weekly) && ($item["Semana emitida"] === "1");
};

$filterByFifteen = function ($item) use ($fifteenly) {
    return ($item["Frequência de visitas"] === $fifteenly) && ($item["Semana emitida"] === "1" || $item["Semana emitida"] === "2");
};

echo "Filtrando por semana" . PHP_EOL;
$filteredByWeek = array_filter($informations, $filterByWeek);
echo "Filtrando por quinzena" . PHP_EOL;
$filteredByFifteen = array_filter($informations, $filterByFifteen);

echo "Reindexando arrays" . PHP_EOL;
sort($filteredByWeek);
sort($filteredByFifteen);
echo "Arrays reindexados" . PHP_EOL;

echo "Salvando json semanal" . PHP_EOL;
$json = json_encode($filteredByWeek);
file_put_contents("semanal.json", $json);
echo "Json salvo" . PHP_EOL;

echo "Salvando json quinzenal" . PHP_EOL;
$json = json_encode($filteredByFifteen);
file_put_contents("quizenal.json", $json);
echo "Json salvo" . PHP_EOL;

$mapToNewFormatWeekly = function ($item) {

    $clientCod = $item["Cliente cod"];
    $seller = $item["Vendedor"];
    $dayOfWeek = $item["Dia emitido"] - 1;
    $folder = "{$seller}{$dayOfWeek}";

    return [
        str_replace("X602", "", $clientCod),
        $seller,
        $folder,
        str_pad($item["Sequência de visitas"], 3, "0", STR_PAD_LEFT),
        $item["Canal cod"]
    ];
};

$mapToNewFormatFiftennly = function ($item) {

    $clientCod = $item["Cliente cod"];
    $seller = $item["Vendedor"];
    $fifteen = $item["Semana emitida"] == "1" ? "5" : "6";
    $dayOfWeek = $item["Dia emitido"] - 1;

    $folder = "{$fifteen}{$dayOfWeek}{$seller[0]}{$seller[2]}";

    return [
        str_replace("X602", "", $clientCod),
        $seller,
        $folder,
        str_pad($item["Sequência de visitas"], 3, "0", STR_PAD_LEFT),
        $item["Canal cod"]
    ];
};

echo "Mapeando para formatação" . PHP_EOL;
$weeklyResult = array_map($mapToNewFormatWeekly, $filteredByWeek);
$fifteenlyResult = array_map($mapToNewFormatFiftennly, $filteredByFifteen);
echo "Formatado" . PHP_EOL;

echo "Salvando json semanal formatado" . PHP_EOL;
$json = json_encode($weeklyResult);
file_put_contents("semanal-formatado.json", $json);
echo "Json salvo" . PHP_EOL;

echo "Salvando json quinzenal formatado" . PHP_EOL;
$json = json_encode($fifteenlyResult);
file_put_contents("quizenal-formatado.json", $json);
echo "Json salvo" . PHP_EOL;

echo "Criando arquivo csv" . PHP_EOL;
$path = $filePath ? "$filePath - " : "";
$path .= 'formatted.csv';
$fp = fopen($path, 'w');

echo "Salvando semanais" . PHP_EOL;
foreach($weeklyResult as $line) {
    fputcsv($fp, $line, ';');
}

echo "Salvando quinzenais" . PHP_EOL;
foreach($fifteenlyResult as $line) {
    fputcsv($fp, $line, ';');
}

echo "Arquivo salvo" . PHP_EOL;
fclose($fp);