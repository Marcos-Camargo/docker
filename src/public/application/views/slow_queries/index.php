<div class="content-wrapper">

    <?php $data['pageinfo'] = "slow_queries";  $this->load->view('templates/content_header',$data); ?>

    <section class="content">
        <div class="row">
            <div class="col-md-12 col-xs-12">
                <?php
                if ($itens){
                    ?>

                    <table class="table table-bordered table-striped table-responsive-lg">
                        <thead>
                            <tr>
                                <th class="text-center">Tempo</th>
                                <th class="text-center">Data</th>
                                <th>URI</th>
                                <th>Query</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($itens as $item){

                                $itemDecode = json_decode($item, true);

                                if (!$item || !$itemDecode){
                                    continue;
                                }

                                $rowClass = '';
                                if ($itemDecode['execution_time'] > 1){
                                    $rowClass = 'bg-danger';
                                }
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><?php echo $itemDecode['execution_time'];?></td>
                                    <td><?php echo $itemDecode['date'];?></td>
                                    <td title="<?php echo $itemDecode['uri']; ?>"><?php echo substr($itemDecode['uri'], 0, 60);?></td>
                                    <td>
                                        <textarea class="textarea" cols="60" rows="1"><?php echo trim($itemDecode['query']);?></textarea>
                                    </td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>

                    <div class="alert alert-info">
                        Total Queries: <?=$total_queries;?>
                    </div>

                <?php
                }else{
                    echo "No Slow Query :)";
                }
                ?>
            </div>
        </div>
    </section>
</div>