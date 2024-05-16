<?php 
foreach($alertas as $key => $error):
    foreach($error as $mensaje):
?>

<div class="alertas <?php echo $key; ?>"><?php echo $mensaje; ?></div>

<?php 
    endforeach;
endforeach;
?>