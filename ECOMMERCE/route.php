<?php

require_once "product.php";

$routes = ['GET' => [], 'POST' => [], 'PATCH' => [], 'DELETE' => []]; //array associativo con i verbi http

function addRoute($method, $path, $callback)  //funzione add route in cui si passano il metodo/verbo http , url della richiesta e la chiamata di ritorno
{
    global $routes; //variabile globale per stabilire la rotta , array associativo precedentemente creato
    $routes[$method][$path] = $callback; //si assegna alla varibile route , il verbo http e il path della richeista e si assegna alla chiamata di ritorno
}

function getRequestMethod()
{
    return $_SERVER['REQUEST_METHOD']; //ritorna il metodo utilizzato (get , post , patch ,delete)
}

function getRequestPath()
{
    $path = $_SERVER['REQUEST_URI'];// recupera il percorso del file e i parametri della query
    $path = parse_url($path, PHP_URL_PATH);//ottiene il percorso dell url
    return rtrim($path, '/');//rimuove il carattere finale dal percorso
}
function handleRequest()
{
    global $routes;//variabile globale

    $method = getRequestMethod();//assegna il metodo alla variabile
    $path = getRequestPath();//assegna il path alla varibile

    //questo if controlla se le 3 chiamate corrispondono e ti rimanda alla funzione di callback corrispondente
    if (isset($routes[$method])) { //controlla se è settata la rotta col metodo
        foreach ($routes[$method] as $routePath => $callback) { //itera sulle rotte e assegna ad ognuna la callback corrispondente
            if (preg_match('#^' . $routePath . '$#', $path, $matches)) { //controlla se l'assegnazione precedente è corretta ovvero se il path route è uguale al path
                call_user_func_array($callback, $matches);//come primo paametro c'è la callback e come secondo un array che contiene al suo interno la funzione callback
                return;
            }
        }
    }

    http_response_code(404); //se non passa i controlli da un 404
    echo "404 Not Found";
}

addRoute('GET', '/products', function () {//metodo get senza id
    $products = Product::FetchAll();//ritorna tutti i prodotti dentro products sottoforma di oggetto
    $data = [];//aray vuoto
    foreach ($products as $product) { //itera sui prodotti e li inserisce dentro array data , parametrizzati e sotto forma di risposta formattata json
        $data[] = [
            'type' => 'products',
            'id' => $product->getId(),
            'attributes' => [
                'nome' => $product->getNome(),
                'marca' => $product->getMarca(),
                'prezzo' => $product->getPrezzo()
            ]
        ];
    }

    header("HTTP/1.1 200 OK"); //se è tutto ok torna 200
    header("Content-Type: application/vnd.api+json");//si inserisce il content type json

    $risposta_json = ['data' => $data]; //si prepara la risposta assegnando alla chiave data , l'array data preparato precedentemente
    echo json_encode($risposta_json, JSON_PRETTY_PRINT); //si esegue l'encode della risposta e con json pretty print lo formatta in modo leggibile
});

addRoute('GET', '/products/(\d+)', function ($matches) {//funzione get con id
    $divisioni = explode('/', $matches);//spezza la stringa in base al "/" e inserisce i pezzi di stringa nell'array divisioni
    $id = end($divisioni); //con la funzione end recupera l'ultimo elemento dell'array divisioni che in questo caso è id
    $product = Product::Find($id);//cerca il prodotto nella tabella in base a quell'id
    header("Location: /products/" . $id); //reinderizza il client al seguente url ovvero alla tabella product , al prodotto con l'id specificato
    header('HTTP/1.1 200 OK');//risposta 200 ok
    header('Content-Type: application/vnd.api+json');//si inserisce il content type json
    if ($product) { //se prodotto esiste , preparo la risposta codificata correttametne il json

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
        $risposta_json = ['data' => $data];


        echo json_encode($risposta_json, JSON_PRETTY_PRINT);//si prepara la risposta assegnando alla chiave data , l'array data preparato precedentemente
    } else {

        http_response_code(404); //errore 404 se non trova il prodotto
        echo json_encode(['error' => 'Prodotto non trovato']);
    }
});

addRoute('POST', '/products', function () { //chiamata post
    $dati_json = json_decode(file_get_contents('php://input'), true); //prende il json inviato dal server , e converte la risposta in un array associativo (true) e lo isnerisce nell'array datijson 
    if ($dati_json && isset($dati_json['data']['attributes'])) { //controlla se sono settati tutti i parametri
        $attributi = $dati_json['data']['attributes']; //si assegna alla variabile attributi gli attributi di data e attributes
        $nuovo_prodotto = Product::Create($attributi); //creazione nuovo prodotto con i parametri della var attributi
        if ($nuovo_prodotto) { //se esiste preparo la risposta json
            $data = [
                'data' => [
                    'type' => 'products',
                    'id' => $nuovo_prodotto->getId(),
                    'attributes' => [
                        'nome' => $nuovo_prodotto->getNome(),
                        'marca' => $nuovo_prodotto->getMarca(),
                        'prezzo' => $nuovo_prodotto->getPrezzo()
                    ]
                ]
            ];
            header("HTTP/1.1 201 Created"); //201 se creato
            header("Content-Type: application/vnd.api+json");//si inserisce il content type json
            echo json_encode($data, JSON_PRETTY_PRINT);//si prepara la risposta assegnando alla chiave data , l'array data preparato precedentemente
        } else {
            http_response_code(500); //errore internal server error 500 
            echo json_encode(['error' => 'Errore durante la creazione del prodotto']); //scritta di errore
        }
    } else {
        http_response_code(400);//errore 400 
        echo json_encode(['error' => 'Dati non validi']);//scritta di erroe
    }
});

addRoute('PATCH', '/products/(\d+)', function ($matches) { //funzione patch di modifica
    $divisioni = explode('/', $matches);//spezza la stringa in base al "/" e inserisce i pezzi di stringa nell'array divisioni
    $id = end($divisioni); //con la funzione end recupera l'ultimo elemento dell'array divisioni che in questo caso è id
    $dati_json = json_decode(file_get_contents("php://input"), true); //prende il json inviato dal server , e converte la risposta in un array associativo (true) e lo isnerisce nell'array datijson 
    $product = Product::Find($id);//cerca il prodotto nella tabella in base a quell'id
    try { //se esistono array datijson e products , si la fa query di update e si prepara la risposta json
        if ($dati_json && $product) {
            $prodotto_aggiornato = $product->Update($dati_json["data"]["attributes"]);
            $data = [
                'type' => 'products', 
                'id' => $prodotto_aggiornato->getId(), 
                'attributes' => [
                    'nome' => $prodotto_aggiornato->getNome(), 
                    'marca' => $prodotto_aggiornato->getMarca(), 
                    'prezzo' => $prodotto_aggiornato->getPrezzo()
                ]
            ];
            $risposta_json = ['data' => $data];
            header("Location: /products/".$id);//reinderizza il client al seguente url ovvero alla tabella product , al prodotto con l'id specificato
            header('HTTP/1.1 200 OK');//200 ok 
            header('Content-Type: application/vnd.api+json');//content type json
            echo json_encode($risposta_json, JSON_PRETTY_PRINT);//si prepara la risposta assegnando alla chiave data , l'array data preparato precedentemente
        } else {

            http_response_code(404);//errore 404
            echo json_encode(['error' => 'Prodotto non trovato']);//scritta di errore
        }
    } catch (PDOException $e) {//se il try non va a buon fine , cattura l'eccezzione e rimanda la risposta al server in json

        header("Location: /products/".$id);//reinderizza il client al seguente url ovvero alla tabella product , al prodotto con l'id specificato
        header('HTTP/1.1 500 INTERNAL SERVER ERROR');//scritta erroe 500
        header('Content-Type: application/vnd.api+json');//type json
        http_response_code(500);//errore 500
        echo json_encode(['error' => 'Errore nell aggiornamento del prodotto']); //scritta di errore
    }
});

addRoute('DELETE', '/products/(\d+)', function ($matches) { //funzione delete
    $divisioni = explode('/', $matches);//spezza la stringa in base al "/" e inserisce i pezzi di stringa nell'array divisioni
    $id = end($divisioni); //con la funzione end recupera l'ultimo elemento dell'array divisioni che in questo caso è id
    $product = Product::Find($id);//cerca il prodotto nella tabella in base a quell'id

    if ($product) {//se il prodotto esiset
        if ($product->Delete()) {//esegue la delete
            header("HTTP/1.1 204 NO CONTENT");//e passa 204 no content perche non ritorna nulla
        } else {
            header("HTTP/1.1 500 INTERNAL SERVER ERROR");//errore 500 se non va a buon fine
            echo json_encode(['error' => 'Errore durante l\'eliminazione del prodotto']);//scritta di errore
        }
    } else {
        header("HTTP/1.1 404 NOT FOUND");//errore 404
        echo json_encode(['error' => 'Prodotto non trovato']);//scritta di errore se non trova il prodotto
    }
});
handleRequest();//si utilizza la funzione per gestire le richieste.

