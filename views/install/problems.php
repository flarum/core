<h2><?php echo $trans[$lng]['problems_hold_up'] ?></h2>

<p><?php echo $trans[$lng]['problems_description'] ?></p>

<div class="Problems">
  <?php foreach ($problems as $problem) { ?>
    <div class="Problem">
      <h3 class="Problem-message"><?php echo $problem['message']; ?></h3>
      <?php if (! empty($problem['detail'])) { ?>
        <p class="Problem-detail"><?php echo $problem['detail']; ?></p>
      <?php } ?>
    </div>
  <?php } ?>
</div>
