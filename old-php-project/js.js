var isAdvancedUpload = function () {
  var div = document.createElement('div');
  return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
}();

//
var $sequencing_form = $('#sequencing-form')
var $upload_form = $('#upload-form');
var file_list = $('#file_list');

var $input = $upload_form.find('input[type="file"]'),
  $label = $upload_form.find('label'),
  $errorBox = $upload_form.find('.box__error'),
  $errorMsg = $upload_form.find('.box__error span'),
  showFiles = function (files) {
    $label.text(files.length > 1 ? ($input.attr('data-multiple-caption') || '').replace('{count}', files.length) : files[0].name);
  };

var is_sequencing = $sequencing_form.length;

if (isAdvancedUpload) {
  $upload_form.addClass('has-advanced-upload');

  var droppedFiles = false;

  $upload_form.on('drag dragstart dragend dragover dragenter dragleave drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
  })
    .on('dragover dragenter', function () {
      $upload_form.addClass('is-dragover');
    })
    .on('dragleave dragend drop', function () {
      $upload_form.removeClass('is-dragover');
    })
    .on('drop', function (e) {
      droppedFiles = e.originalEvent.dataTransfer.files;
      showFiles(droppedFiles);
      $upload_form.trigger('submit');
    });

}
$input.on('change', function (e) { // when drag & drop is NOT supported
  showFiles(e.target.files);
  $upload_form.trigger('submit');
});


$upload_form.on('submit', function (e) {
  if ($upload_form.hasClass('is-uploading')) return false;

  $upload_form.addClass('is-uploading').removeClass('is-error');

  if (isAdvancedUpload) {

    e.preventDefault();

    if (droppedFiles) {
      var ajaxData = new FormData();
    } else {
      var ajaxData = new FormData($upload_form.get(0));
    }
    if (is_sequencing) {
      ajaxData.append('is_sequencing', is_sequencing);
      $sequencing_form.find(':input').each(function () {
        ajaxData.append(this.name, $(this).val());
      });
    }
    if (droppedFiles) {
      $.each(droppedFiles, function (i, file) {
        ajaxData.append($input.attr('name'), file);
      });
    }


    $.ajax({
      url: $upload_form.attr('action'),
      type: $upload_form.attr('method'),
      data: ajaxData,
      dataType: 'json',
      cache: false,
      contentType: false,
      processData: false,
      complete: function () {
        $upload_form.removeClass('is-uploading');
      },
      success: function (data) {
        $upload_form.addClass(data.success == true ? 'is-success' : 'is-error');
        if (data.reload) {
          location.reload();
        } else if (data.new_file_list) {
          $.get('file_list.php?sequencing=' + is_sequencing, function (response) {
            file_list.html(response);
          });
        } else {
          $errorMsg.text(data.error);
          $errorBox.show();
          alert(data.error);
          $label.text('-');
        }

      },
      error: function () {
        // Log the error, show an alert, whatever works for you
      }
    });

  } else {
    // ajax for legacy browsers
  }
});


// ...

$(function () {

  $('#merger-form').on('change', ':input', function () {
    if (this.name == 'cand_num') {
      return;
    }
    sessionStorage.setItem(this.name, this.value);
  }).find(':input').each(function () {
    if (this.name == 'cand_num') {
      return;
    }
    var v = sessionStorage.getItem(this.name);
    if (v) {
      this.value = v;
    }
  });
  $sequencing_form.on('change', ':input', function () {
    sessionStorage.setItem(this.name, this.value);
  }).find(':input').each(function () {
    var v = sessionStorage.getItem(this.name);
    if (v) {
      this.value = v;
    }
  });
  $sequencing_form.find('[name="cand_num"]').on('change', function () {
    if (this.value) {
      $upload_form.show();
    } else {
      $upload_form.hide();
    }
  }).trigger('change');
});