<?php
use \koolreport\widgets\koolphp\Table;
?>
<div class='report-content'>
    <div class="text-center">
        <h1>Test pipeTree</h1>
        <h4>All</h4>
    </div>
        <?php
        Table::create(array(
            "dataSource"=>$this->store("all")
        ));
        ?>
        <h4>Group</h4>
        <?php
        Table::create(array(
            "dataSource"=>$this->store("group")
        ));
        ?>
</div>