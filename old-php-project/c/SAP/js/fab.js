// https://codepen.io/petja/pen/OVRYMq
$(function(){
    var $fab_control = $(".fab-control");
    var $fab_children = $(".fab.child");
    var $fab_back = $(".fab-backdrop");
    if ($fab_control.length == 0 && $fab_children.length == 0){
        return;
    }
    var l=0;
    $fab_children.each(function(){
        if ($(this).data("subitem") > l){
            l = $(this).data("subitem");
        }
    });
    $fab_children.each(function(){
        if ($(this).data("subitem") == null){
            l++;
            $(this).data("subitem", l);
        }
    });
    if ($fab_control.length == 0){
        $fab_control = $('<div class="fab fab-control"><i class="glyphicon glyphicon-option-vertical"></i></span></div>');
        $("body").append($fab_control);
        $fab_control.css("z-index",101);
        $fab_children.css("z-index",102);
    }
    if ($fab_back.length == 0){
        $fab_back = $("<div>").addClass("fab-backdrop");
        $("body").append($fab_back);
        $fab_back.css("z-index",100);
        $fab_control.css("z-index",101);
        $fab_children.css("z-index",102);
    }
	$(".fab,.fab-backdrop").click(function(){
		if($fab_back.is(":visible")){
			$fab_back.fadeOut(125);
			$fab_children
				.stop()
				.animate({
					bottom	: $fab_control.css("bottom"),
					opacity	: 0
				},125,function(){
					$(this).hide();
				});
		}else{
			$fab_back.fadeIn(125);
			$fab_children.each(function(){
				$(this)
					.stop()
					.show()
					.animate({
						bottom	: (parseInt($fab_control.css("bottom")) + parseInt($fab_control.outerHeight()) + 70 * $(this).data("subitem") - $(".fab.child").outerHeight()) + "px",
						opacity	: 1
					},125);
			});
		}
	});
});