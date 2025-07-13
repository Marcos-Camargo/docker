<?php
use App\Libraries\Enum\ComissioningType;
if (isset($error) && $error){
    echo $error;
    return;
}
?>
<ul>
    <?php
    if (isset($store) && $store){
    ?>
        <li>
            Seller: <?php echo $store['name']; ?>
        </li>
    <?php
    }
    ?>
    <li>
        Nível Hierarquico: <?php echo ComissioningType::getName($entry['type']); ?>
    </li>
    <?php
    if (isset($item) && $item){
        if ($entry['type'] != ComissioningType::PRODUCT) {
        ?>
            <li>
                <?php echo ComissioningType::getName($entry['type']); ?>: <?php echo $entry['name']; ?>
            </li>
            <li>
                Comissão: <?php echo $item['comission']; ?>%
            </li>
        <?php
        }
    }else{
        ?>
        <li>
            Comissão: Nenhum item de comissionamento encontrado
        </li>
    <?php
    }
    ?>
    <li>
        Vigência Inicial: <?php echo datetimeBrazil($entry['start_date']); ?>
    </li>
    <li>
        Vigência Final: <?php echo datetimeBrazil($entry['end_date']); ?>
    </li>
    <li>
        Status: <?php echo $entry['status']; ?>
    </li>
    <li>
        Data de Criação: <?php echo datetimeBrazil($entry['created_at']); ?>
    </li>
    <?php
    if (isset($creationLog) && $creationLog){
    ?>
        <li>
            Criador: <?php echo $creationLog['user']['email']; ?>
        </li>
    <?php
    }
    if ($entry['type'] == ComissioningType::PRODUCT) {
        $url = base_url('commissioning/download/'.$entry['id']);
        ?>
        <li>
            Arquivo: <a href='<?=$url?>'>Baixar Arquivo <i class='fas fa-download'></i></a>
        </li>
    <?php
    }
    ?>
</ul>
