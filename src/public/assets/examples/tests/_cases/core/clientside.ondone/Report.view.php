<?php
    use \koolreport\widgets\koolphp\Table;
?>
<div class='report-content'>
    <div class="text-center">
        <h1>Test</h1>
    </div>
        <?php
        Table::create(array(
            "name"=>"mytable",
            "dataSource"=>array(
                array("name"=>"Peter","age"=>35),
                array("name"=>"David","age"=>36),
            )
        ));
        ?>

        <script>
        KoolReport.load.onDone(function(){
            document.getElementById("test").innerText = "run";
        });
        </script>
</div>