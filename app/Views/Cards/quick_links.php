<div class="card top-row-card">
  <div class="card-body">
    <h5 class="card-title"><?php echo $card_title; ?></h5>
    <hr>
    <?php foreach($links as $link): ?>
      <p class="card-text"><a href="<?php echo $link["link_url"]; ?>"><?php echo $link["link_text"] ?></a></p>
    <?php endforeach; ?>
  </div>
</div>
