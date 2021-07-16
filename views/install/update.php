<h2><?php echo $trans[$lng]['update_title'] ?></h2>

<p><?php echo $trans[$lng]['update_description'] ?></p>

<form method="post">
  <div id="error" style="display:none"></div>

  <div class="FormGroup">
    <div class="FormField">
      <label><?php echo $trans[$lng]['mysql_password_label'] ?></label>
      <input type="password" name="databasePassword">
    </div>
  </div>

  <div class="FormButtons">
    <button type="submit"><?php echo $trans[$lng]['update_title'] ?></button>
  </div>
</form>

<script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
<script>
$(function() {
  $('form :input:first').select();

  $('form').on('submit', function(e) {
    e.preventDefault();

    var $button = $(this).find('button')
      .text('<?php echo $trans[$lng]['wait_label'] ?>')
      .prop('disabled', true);

    $.post('', $(this).serialize())
      .done(function() {
        window.location.reload();
      })
      .fail(function(data) {
        $('#error').show().text('<?php echo $trans[$lng]['went_wrong_label'] ?>\n\n' + data.responseText);

        $button.prop('disabled', false).text('<?php echo $trans[$lng]['update_title'] ?>');
      });

    return false;
  });
});
</script>
