<?php
function Leggi($path)
{
    if(file_exists($path))
        return json_decode(file_get_contents($path));
    else
        return Array();
}

function Salva($dati, $path)
{
    file_put_contents($path, json_encode($dati));
}

function CheckValue($check, $mode, $path)
{
    $Users = Leggi($path);

    for($i = 0; $i < count($Users); $i++)
    {
        if($Users[$i]->$mode == $check)
        {
                return true;
        }
            
    }
    return false;
}

function AdminMode($username, $password)
{
    if($username == "admin" && $password == "admin")
        return true;
    else
        return false;
}

function IsDBEmpty($path)
{
    $Users = Leggi($path);
    if(count($Users) == 0)
        return true;
    else
        return false;
}

function IdToKey($id)
{
    $Users = Leggi("DB.json");
    for($i = 0; $i < count($Users); $i++)
    {
        if($Users[$i]->id == $id)
            return $i;
    }
}

function Logout()
{
    session_unset();
    session_destroy();
    header("Location: Homepage.php");
}

function CheckActivation($check, $mode)
{
    if(IsDBEmpty("DB.json"))
        echo '<script type="text/javascript">alert("Nessun utente registrato");document.location="Register.php"</script>';
    else
    {
        $Users = Leggi("DB.json");
        for($i = 0; $i < count($Users); $i++)
        {
            if($Users[$i]->$mode == $check)
            {
                if($Users[$i]->status == false)
                    return false;
                else
                    return true;
            }
        }
    
        return false;
    }    
}

function CorrectPassword($check, $mode, $password)
{
    $Users = Leggi("DB.json");
    for($i = 0; $i < count($Users); $i++)
    {
        if($Users[$i]->password == $password && $Users[$i]->$mode == $check)
            return $Users[$i]->id;
    }

    return false;
}

function IdToName($id)
{
    $Users = Leggi("DB.json");
    for($i = 0; $i < count($Users); $i++)
    {
        if($Users[$i]->id == $id)
            return $Users[$i]->nome.' '.$Users[$i]->cognome;
    }
}

function PicExists($id)
{
    $filepath = 'products/'.$id.'';
    if(file_exists($filepath.".png"))
        return $filepath.".png"; 
    else if(file_exists($filepath.".jpeg"))
        return $filepath.".jpeg";  
    else if(file_exists($filepath.".jpg"))
        return $filepath.".jpg";     
    else
        return false;     
}

function SendMail($email, $mailtext, $object)
{
    $mailtext = wordwrap($mailtext, 80);
    mail ($email, $object, $mailtext, "From: sender\'s email");
}

function StampaProdotti($mode)
{
    $Prodotti = Leggi("DBProducts.json");

    if(count($Prodotti) == 0)
    {
        echo 'Nessun prodotto in vendita';
        echo '<h2>Incasso totale: '.$Totincasso[0]->incasso.'€</h2>';
    }
    else
    {
        if($mode == "admin")
        {
            $Totincasso = Leggi("Admin.json");
            echo '<form method="POST" action="DeleteProducts.php">';
            echo '<table border="1" align="center"><td>Immagine</td><td>Id</td><td>Nome</td><td>Quantità</td><td>Prezzo</td><td>Elimina</td>';
            for($i = 0; $i < count($Prodotti); $i++)
            {
                $pic = PicExists($Prodotti[$i]->id);
                echo '<tr><td><img src="'.$pic.'" width="50px" height="50px"/></td><td>'.$Prodotti[$i]->id.'</td><td>'.$Prodotti[$i]->nome.'</td><td>'.$Prodotti[$i]->quantity.'</td><td>'.$Prodotti[$i]->prezzo.'€'.'</td><td><input type="checkbox" name="prodotti[]" value="'.$Prodotti[$i]->id.'"</td></tr>';  
            }
            echo '</table><br><input type="submit" value="Rimuovi selezionati"/></form><h2>Incasso totale: '.$Totincasso[0]->incasso.'€</h2>';
        }
        else if ($mode == "user")
        {
            echo '<form method="POST" action="BuyProducts.php">';
            echo '<table border="1" align="center"><td>Immagine</td><td>Id</td><td>Nome</td><td>Quantità</td><td>Prezzo</td><td>Acquista</td>';
            for($i = 0; $i < count($Prodotti); $i++)
            {
                $pic = PicExists($Prodotti[$i]->id);
                echo '<tr><td><img src="'.$pic.'" width="50px" height="50px"/></td><td>'.$Prodotti[$i]->id.'</td><td>'.$Prodotti[$i]->nome.'</td><td>'.$Prodotti[$i]->quantity.'</td><td>'.$Prodotti[$i]->prezzo.'€'.'</td><td><input type="checkbox" name="prodotti[]" value="'.$Prodotti[$i]->id.'"<br><input type="number" name="quant[]" value="'.$Prodotti[$i]->id.'" min="1" max="'.$Prodotti[$i]->quantity.'"</td></tr>';  
            }
            echo '</table><br><input type="submit" value="Acquista selezionati"/></form>';
        }
        
    }

    
}

function CheckProduct($nome)
{
    $Prodotti = Leggi("DBProducts.json");
    
    if(count($Prodotti) != 0)
    {
        for($i = 0; $i < count($Prodotti); $i++)
        {
            if($Prodotti[$i]->nome == $nome)
                return $i;
        }
    }

    return -1;
}

function DeleteProd($arr, $path)
{
    $Prodotti = Leggi($path);
    $Selected = GetSelected($Prodotti, $path, $arr);
    
    for($i = 0; $i < count($Selected); $i++)
    {
        $pic = PicExists($Prodotti[$Selected[$i]]->id);
        unlink($pic);
        unset($Prodotti[$Selected[$i]]);
    }

    Salva(array_values($Prodotti), $path);
    echo '<script type="text/javascript">alert("Prodotti rimossi");document.location="Homepage.php"</script>'; 
}

function BuyProd($arr, $path, $quant, $id)
{
    $Prodotti = Leggi($path);
    $Selected = GetSelected($Prodotti, $path, $arr);
    $List = GetItemsOverview($Selected, $quant);
    $incasso = 0.0;
    $TotaleIncasso = Leggi("Admin.json");

    for($i = 0; $i < count($Selected); $i++)
    {
        $Prodotti[$Selected[$i]]->quantity -= $quant[$Selected[$i]];
        $incasso += ($quant[$Selected[$i]]*$Prodotti[$Selected[$i]]->prezzo);
        if($Prodotti[$Selected[$i]]->quantity == 0)
        {
            $pic = PicExists($Prodotti[$Selected[$i]]->id);
            unlink($pic);
            unset($Prodotti[$Selected[$i]]);
        }
    }

    $msg = "Grazie per il tuo ordine, ecco gli oggetti acquistati con la relativa quantità: ";
    for($i = 0; $i < count($List); $i++)
    {
        $msg = $msg.$List[$i]->nome." ".$List[$i]->quantity."x, ";
    }
    $TotaleIncasso[0]->incasso += $incasso;
    Salva($TotaleIncasso, "Admin.json");
    Salva(array_values($Prodotti), $path);

    SendMail(GetSessionEmail($id), $msg, "Resoconto acquisto");
    echo '<script type="text/javascript">alert("Prodotti acquistati. È stata inviata un\' email di riepilogo con gli articoli acquistati");document.location="Homepage.php"</script>'; 
}

function GetItemsOverview($Selected, $quant)
{
    $Prodotti = Leggi("DBProducts.json");
    $List = Array();
    for($i = 0; $i < count($Selected); $i++)
    {
        $Prodotti[$Selected[$i]]->quantity = $quant[$i];
        $List[] = $Prodotti[$Selected[$i]];
    }
    return $List;
}

function GetSessionEmail($id)
{
    $Users = Leggi("DB.json");
    for($i = 0; $i < count($Users); $i++)
    {
        if($Users[$i]->id == $id)
            return $Users[$i]->email;
    }
}

function GetSelected($Products, $path, $arr)
{
    $Sel = Array();
    for($i = 0; $i < count($Products); $i++)
    {
        for($j = 0; $j < count($arr); $j++)
        {
            if($Products[$i]->id == $arr[$j])
            {
                $Sel[] = $i;
            }
        }
    }

    return $Sel;
}

function AddProductView()
{
    echo '<h1>Aggiungi Prodotto</h1><form method="POST" action="AddProduct.php" enctype="multipart/form-data">
            Nome <input type="text" name="nome"/><br>
            Prezzo <input type="text" name="prezzo"/><br>
            Quantità <input type="number" name="quantity"/><br>
            Foto <input type="file" name="fotoprodotto" accept="image/png,image/jpeg"/><br><br>
            <input type="submit" value="Aggiungi prodotto"/>
            </form><br>';
}

function GetLastId($path)
{
    $Prodotti = Leggi($path);

    $last = 0;

    for($i = 0; $i < count($Prodotti); $i++)
    {
        if($Prodotti[$i]->id > $last)
            $last = $Prodotti[$i]->id;
        
    }
    return $last;
}
?>