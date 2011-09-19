function checkquiz(e){
  var target = jQuery(e);
  var form = target.parents('form');
  var quiz = form.parent();
  var quiztype = form.find("input[name='qtype']").val();
  var numitems = form.find("input[name='qnum']").val();
  var solution = form.find("input[name='qsol']").val();
  var solutions = solution.split(',');
  if (quiztype == 'vf'){
	var values = [];
    form.find('td.ko').removeClass('ko');
    form.find('td.ok').removeClass('ok');
    for (var i=1; i<=numitems; i++){
      var radio = form.find('input[name="sol_' + i + '"]:checked');
      var value = radio.val();
      if(value == solutions[i-1]){
        radio.parent().addClass('ok');
        values.push(value);
      }else{
        radio.parent().addClass('ko');
        values.push('');
      }
    }
  }else if(quiztype == 'choice'){
	var values = true;
    form.find('td.ko').removeClass('ko');
    form.find('td.ok').removeClass('ok');
    for (var i=1; i<=numitems; i++){
      var checkbox = form.find('input[name="sol_' + i + '"]');
      if(checkbox.is(':checked')){
        //values.push('V');
        //if (solutions[i-1] == 'V'){
        if(inArray(i, solutions)){
          checkbox.parent().addClass('ok');
        }else{
          checkbox.parent().addClass('ko');
      	  values = false;
        }
      }else{
        //values.push('F');
          //if (solutions[i-1] == 'F'){
          if(!inArray(i, solutions)){
            checkbox.parent().addClass('ok');
          }else{
            checkbox.parent().addClass('ko');
            values = false;
          }
      }
    }
  }
  var res = '';
  if(quiztype !== 'vf'){
    if (values){
	  res = '<p class="ok">Correcte</p>';
	}else{
	  res = '<p class="ko">Erroni</p>';
	}
  }else{
	  var resp = values.join(',');
	  if (solution == resp){
	    res = '<p class="ok">Correcte</p>';
	  }else{
	    res = '<p class="ko">Erroni</p>';
	  }
  }
  //showsolution(form.attr('id'), res);
  showsolution(target, res);
}

function showsolution(target, text){
  //jQuery('div.quiz_result').remove();
  //var form = jQuery('#'+target);
  var form = target.parents('form');
  //var form = target;
  var quiz = target.parents('div');
  if (quiz.is('div.quiz')){
    jQuery(form).parent().children(".quiz_result").hide().fadeOut("slow").html(text).fadeIn("slow");
    //var divsolucio = jQuery('<div class="quiz_result">'+text+'</div>').hide();
    //divsolucio.insertAfter(form).fadeIn("slow");
  }else{
    alert(text);
  }
} 

function inArray(needle, haystack)
{
    for(var key in haystack)
    {
    	needle = needle + '';//to String
        if(needle === haystack[key])
        {
            return true;
        }
    }
    return false;
}