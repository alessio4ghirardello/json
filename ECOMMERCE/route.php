<?php

require_once "product.php";


$routes = ['GET' => [], 'POST' => [], 'PATCH' => [], 'DELETE' => []];


function addRoute($method, $path, $callback)
{
    global $routes;
    $routes[$method][$path] = $callback;
}


function getRequestMethod()
{
    return $_SERVER['REQUEST_METHOD'];
}


function getRequestPath()
{
    $path = $_SERVER['REQUEST_URI'];
    $path = parse_url($path, PHP_URL_PATH);
    return rtrim($path, '/');
}


function handleRequest()
{
    global $routes;

    $method = getRequestMethod();
    $path = getRequestPath();


    if (isset($routes[$method])) {
        foreach ($routes[$method] as $routePath => $callback) {

            if (preg_match('#^' . $routePath . '$#', $path, $matches)) {
                // Chiamata al callback passando l'ID come parametro
                call_user_func_array($callback, $matches);
                return;
            }
        }
    }

    http_response_code(404);
    echo "404 Not Found";
}


addRoute('GET', '/products/(\d+)', function ($matches) {

    $parts = explode('/', $matches);
    $id = end($parts);
    $product = Product::Find($id);
    header("Location: /products/" . $id);
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/vnd.api+json');
    if ($product) {

        $data =
            [
                'type' => 'products',
                'id' => $product->getId(),
                'attributes' =>
                    [
                        'nome' => $product->getNome(),
                        'marca' => $product->getMarca(),
                        'prezzo' => $product->getPrezzo()
                    ]
            ];
        $response = ['data' => $data];


        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {

        http_response_code(404);
        echo json_encode(['error' => 'Prodotto non trovato']);
    }
});

addRoute('GET', '/products', function () {
    $products = Product::FetchAll();
    $data = [];
    foreach ($products as $product) {
        $data[] =
            [
                'type' => 'products',
                'id' => $product->getId(),
                'attributes' =>
                    [
                        'nome' => $product->getNome(),
                        'marca' => $product->getMarca(),
                        'prezzo' => $product->getPrezzo()
                    ]
            ];
    }
    header("Location: /products");
    header('HTTP/1.1 200 OK');
    header('Content-Type: application/vnd.api+json');

    $response = ['data' => $data];
    echo json_encode($response, JSON_PRETTY_PRINT);
});

addRoute('POST', '/products', function(){

    $data=[];
    if (isset($_POST['data']))
        $postData = $_POST;
    else
        $postData = json_decode(file_get_contents("php://input"), true);
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($postData['data']['attributes']['marca'], $postData['data']['attributes']['nome'], $postData['data']['attributes']['prezzo']))
        {
            $newProduct = Product::Create($postData["data"]["attributes"]);
            $data =
                [
                    'type' => 'products',
                    'id' => $newProduct->getId(),
                    'attributes' =>
                        [
                            'nome' => $newProduct->getNome(),
                            'marca' => $newProduct->getMarca(),
                            'prezzo' => $newProduct->getPrezzo()
                        ]
                ];

            $response = ['data' => $data];
            echo json_encode($response, JSON_PRETTY_PRINT);
            header("Location: /products");
            header('HTTP/1.1 201 CREATED');
            http_response_code(201);
            header('Content-Type: application/vnd.api+json');
        }
        else
        {
            http_response_code(500);
        }

    } catch (PDOException $e) {
        header("Location: /products");
        header('HTTP/1.1 500 INTERNAL SERVER ERROR');
        header('Content-Type: application/vnd.api+json');
        http_response_code(500);
        echo json_encode(['error' => 'Errore nella creazione del prodotto']);
    }
});

addRoute('PATCH', '/products/(\d+)', function ($matches) {


    $parts = explode('/', $matches);
    $id = end($parts);
    $patchData = json_decode(file_get_contents("php://input"), true);
    $product = Product::Find($id);

    try {
        if ($patchData && $product) {
            $updatedProduct = $product->Update($patchData["data"]["attributes"]);

            $data = [
                'type' => 'products',
                'id' => $updatedProduct->getId(),
                'attributes' => [
                    'nome' => $updatedProduct->getNome(),
                    'marca' => $updatedProduct->getMarca(),
                    'prezzo' => $updatedProduct->getPrezzo()
                ]
            ];


            $response = ['data' => $data];


            header("Location: /products/".$id);
            header('HTTP/1.1 200 OK');
            header('Content-Type: application/vnd.api+json');


            echo json_encode($response, JSON_PRETTY_PRINT);
        } else {

            http_response_code(404);
            echo json_encode(['error' => 'Prodotto non trovato']);
        }
    } catch (PDOException $e) {

        header("Location: /products/".$id);
        header('HTTP/1.1 500 INTERNAL SERVER ERROR');
        header('Content-Type: application/vnd.api+json');
        http_response_code(500);
        echo json_encode(['error' => 'Errore nell aggiornamento del prodotto']);
    }
});



addRoute('DELETE', '/products/(\d+)', function ($id) {

    $newID = str_split($id, 10);
    $product = Product::Find($newID[1]);
    if ($product) {
        if ($product->Delete()) {
            header("Location: /products/(\d+)");
            header('HTTP/1.1 204 NO CONTENT');
            header('Content-Type: application/vnd.api+json');

            http_response_code(204);
        } else {
            header("Location: /products/(\d+)");
            header('HTTP/1.1 500 INTERNAL SERVER ERROR');
            header('Content-Type: application/vnd.api+json');
            http_response_code(500);
            echo json_encode(['error' => 'Errore durante l\'eliminazione del prodotto']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Prodotto non trovato']);
    }
});

handleRequest();