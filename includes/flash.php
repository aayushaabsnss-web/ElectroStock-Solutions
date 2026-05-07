<?php
foreach(['success'=>'alert-success','error'=>'alert-danger','warning'=>'alert-warning'] as $k=>$cls):
  if($m=getFlash($k)): ?><div class="alert <?= $cls ?>"><?= h($m) ?></div><?php
  endif;
endforeach;
