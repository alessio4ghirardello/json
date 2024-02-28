<?php
require "Dbmanager.php";
class Product
{
    private $id;
    private $nome;
    private $prezzo;
    private $marca;


    public function getId()
    {
        return $this->id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function getPrezzo()
    {
        return $this->prezzo;
    }

    public function setPrezzo($prezzo)
    {
        $this->prezzo = $prezzo;
    }

    public function getMarca()
    {
        return $this->marca;
    }

    public function setMarca($marca)
    {
        $this->marca = $marca;
    }

    public static function Find($id)
    {
        $pdo = self::Connect();
        $stmt = $pdo->prepare("SELECT * FROM ecommerce.products WHERE id = :id");
        $stmt->bindParam(":id", $id);
        if ($stmt->execute()) {
            return $stmt->fetchObject("product");
        } else {
            return false;
        }
    }

    public static function Create($params)
    {
        $pdo = self::Connect();
        $stmt = $pdo->prepare("INSERT INTO ecommerce.products (nome,marca,prezzo) VALUES (:nome,:marca,:prezzo)");
        $stmt->bindParam(":nome", $params["nome"]);
        $stmt->bindParam(":marca", $params["marca"]);
        $stmt->bindParam(":prezzo", $params["prezzo"]);
        if ($stmt->execute()) {
            $stmt = $pdo->prepare("SELECT * FROM ecommerce.products ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            return $stmt->fetchObject("product");
        } else {
            throw new PDOException("Errore Nella Creazione");
        }
    }

    public function Update($params)
    {
        $pdo = self::Connect();
        $stmt = $pdo->prepare("UPDATE ecommerce.products SET nome = :nome, marca = :marca, prezzo = :prezzo WHERE id = :id");
        $stmt->bindParam(":id",$this->id);
        $stmt->bindParam("nome",$params["nome"]);
        $stmt->bindParam("marca",$params["marca"]);
        $stmt->bindParam("prezzo",$params["prezzo"]);
        if($stmt->execute())
        {
            $stmt = $pdo->prepare("SELECT * FROM ecommerce.products WHERE id = :id");
            $stmt->bindParam(":id",$this->id);
            $stmt->execute();
            return $stmt->fetchObject("product");
        }
        else
        {
            return false;
        }
    }

    public static function FetchAll()
    {
        $pdo = self::Connect();
        $stmt = $pdo->query("SELECT * FROM ecommerce.products");
        return $stmt->fetchAll(PDO::FETCH_CLASS, 'product');

    }

    public function Delete()
    {
        if(!$this->getId())
        {
            return false;
        }
        $id = $this->getId();
        $pdo = self::Connect();
        $stmt = $pdo->prepare("DELETE FROM ecommerce.products WHERE id = :id");
        $stmt->bindParam(':id',$id,PDO::PARAM_INT);
        $stmt->execute();
        return true;
    }

    public static function Connect()
    {
        return DbManager::Connect("ecommerce");
    }


}