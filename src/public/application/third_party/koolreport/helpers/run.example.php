<?php
include "common.php";
// $example = json_decode(file_get_contents("_example.json"),true);
?>
<!DOCTYPE html>
    <script type="text/javascript" src="<?php echo $root_url; ?>/assets/theme/js/jquery.min.js"></script>
    <script type="text/javascript" src="<?php echo $root_url; ?>/assets/theme/js/bootstrap.bundle.min.js"></script>

    <script type="text/javascript" src="<?php echo $root_url; ?>/assets/js/highlight.min.js"></script>
    <script type="text/javascript" src="<?php echo $root_url; ?>/assets/js/showdown.js"></script>
    <?php // include "nav.php"; ?>
    <?php // include "online_link.php"; ?>
    <main role="main" class="container">
        <?php include "run.php"; ?>
        <?php // include "example_meta.php"; ?>
    </main>
    <?php // include "footer.php"; ?>
    <script type="text/javascript">hljs.initHighlightingOnLoad();</script>
