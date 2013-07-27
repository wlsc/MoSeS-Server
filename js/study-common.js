/* Datepicker format */
$('[name="start_date"]').datepicker({
  format: 'yyyy-mm-dd'
});

$('[name="end_date"]').datepicker({
  format: 'yyyy-mm-dd'
});
/* ---------------- */

$('[name="btnAddSurvey"]').click(function(e){
    e.preventDefault();
    
    // get the parent of selected stuff
    var p = $(this).parent().parent().parent();
    
    p.find('[name="survey_controls"]').show();
    p.find('[name="survey_container"]').hide();
    
    $(this).hide();
    
});

$('[name="btnAddSurveyOK"]').click(function(e){
    e.preventDefault();
    
    // get the parent of selected stuff
    var p = $(this).parent().parent().parent();
    
    if(parseInt($('#survey_select :selected').val()) != 9001){
    
        // Requesting server for questions for selected survey (ID)
        $.post("content_provider.php", { 'get_questions': $('#survey_select :selected').val(), 'get_questions_pwd' : 6767 })
            .done(function(result) {
                if(result){
                    
                    var data = $.parseJSON(result);
                    
                    var content = '<div class="row-fluid" style="border:2px solid #CCC;" name="survey_container_9001">'+
                                  '<div class="span10" name="survey_body">'+
                                  '<!--Body content-->'+
                                  '<div class="survey_question_container">';
                                 
                   for(var i=0; i < data.length; i++) {
                        content += "#"+(i+1)+" "+data[i]+"<br>";
                   }                        
                   
                   content += '</div>'+
                              '</div>'+
                              '<div class="span2" name="survey_sidebar">'+
                              '<!--Sidebar content-->'+
                              '</div>'+     
                              '<div class="span1"><button class="btn btn-danger btnRemoveSurvey">X</button></div>'+
                              '</div>';
                         
                   $('#content_appears_here').append(content);
                    
                    // reset questions counter
                    surveyQuestionNumber = 1;
                }   
        });
        
    }else{
        
       var content = '<div class="row-fluid" style="border:2px solid #CCC;" name="survey_container_9001">'+
                  '<div class="span10" name="survey_body">'+
                  '<!--Body content-->'+
                  '<div class="survey_question_container">'+
                  'Compose your questions below!<br>'+
                  '  <div>'+
                  '    <div class="survey_elements_container">'+
                  '      <select class="survey_elements" name="survey_question_type">'+
                  '         <option value="1">Yes/No question</option>'+
                  '         <option value="2">Text question</option>'+
                  '         <option value="3">Scale question</option>'+
                  '         <option value="4">Multiple choice question</option>'+
                  '         <option value="5">Single choice question</option>'+
                  '      </select>'+
                  '      <label class="survey_elements" style="display: none;">Number of answers:</label>'+
                  '      <input type="text" title="Number of answers" value="5" maxlength="2" style="width: 1.2em; display: none;">'+
                  '      <button class="btn btn-success survey_elements btnAddQuestionOK">OK</button>'+
                  '    </div>'+
                  '  </div>'+
                  '</div>'+
                  '<input type="hidden" class="survey_questions_counter" value="1">'+
                  '</div>'+
                  '<div class="span1"><button class="btn btn-danger btnRemoveSurvey">X</button></div>'+
                  '</div>';
        
        $('#content_appears_here').append(content);
    }
    
});

// on change question type in survey 
$('#content_appears_here').on('change','[name="survey_question_type"]',function(e) {
    e.preventDefault();
    switch(parseInt($(this).val())){
        // YES/No question
        case 1: // hide number of questions
                $(this).parent().find(':text').hide();
                $(this).parent().find('label').hide();
                break;
                
        // Text question
        case 2: $(this).parent().find(':text').hide();
                $(this).parent().find('label').hide();
                break;
                
        // Scale question
        case 3: $(this).parent().find(':text').hide();
                $(this).parent().find('label').hide();
                break;
                
        // Multiple choice question
        case 4: $(this).parent().find(':text').show();
                $(this).parent().find('label').show();
                break;
                
        // Single choice question
        case 5: $(this).parent().find(':text').show();
                $(this).parent().find('label').show();
                break;
    }
    return false;
});

// remove whole survey
$('#content_appears_here').on('click','.btnRemoveSurvey',function(e) {
    e.preventDefault();
    $(this).parent().parent().remove();
    return false;
});

// remove question from survey
$('#content_appears_here').on('click','.survey_remove_question',function(e){
    e.preventDefault();
    $(this).parent().remove();
    return false; 
});

/* SURVEY CONTROLS */

$('#content_appears_here').on('click', '.btnAddQuestionOK', function(e){
    e.preventDefault();
    
    var parentForQCounter = $(this).parent().parent().parent().parent();
    var quantity = parseInt($(this).parent().find(':text').val());
    var p = $(this).parent().parent().parent();
                                                  
    p.append('<div></div>');
    // copy survey control
    $(this).parent().find(':text').hide();
    $(this).parent().find('label').hide();
    p.find(':last').html($(this).parent().parent().html());
    
    // Question field (common content for all questions)
    var content = '<span class="survey_question_number">#'+parentForQCounter.find('.survey_questions_counter').val()+'</span> '+
                  '<input type="text" name="question" placeholder="Type here your question">'+
                  '<button class="btn btn-link btnRemoveQuestion">Remove question</button>'+
                  '<br>';
      
    switch(parseInt($(this).parent().find('[name="survey_question_type"]').val())){
        // YES/NO Question
        case 1: // compose all answers
                var answers = '<ul>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">Yes</span></li>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">No</span></li>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">Not sure</span></li>'+
                              '</ul>';
                 
                content += answers; 
                // finally append to dom
                $(this).parent().parent().append(content);
                break;
                
        // Text question
        case 2: // compose all answers
                var answers = '<ul>'+
                              '<li><textarea name="answer" cols="20" rows="3" disabled="disabled" placeholder="Answer will be here..."></textarea></li>'+
                              '</ul>';
                 
                content += answers;
                
                // finally append to dom
                $(this).parent().parent().append(content);
                break; 
                
        // Scale question        
        case 3: // compose all answers
                var answers = '<ul>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">"Strongly Disagree"</span></li>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">"Disagree"</span></li>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">"Neither Agree nor Disagree"</span></li>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">"Agree"</span></li>'+
                              '<li><input type="radio" name="answer" disabled="disabled"><span class="survey_q_element">"Strongly Agree"</span></li>'+
                              '</ul>';
                 
                content += answers;
                
                // finally append to dom
                $(this).parent().parent().append(content);
                break;
                
        // Multiple choice
        case 4: // compose all answers
                var answers = '<ul>';
                    for(var i=1; i <= quantity; i++){
                       answers += '<li><input type="checkbox" value="'+ i +'" disabled="disabled"><span><input type="text" placeholder="Answer here"></span></li>'; 
                    }
                answers += '</ul>';
                 
                content += answers;
                
                // finally append to dom
                $(this).parent().parent().append(content);
                break;
            
                
        case 5: // compose all answers
                var answers = '<ul>';
                    for(var i=1; i <= quantity; i++){
                       answers += '<li><input type="radio" name="survey_single_choice" value="'+ i +'" disabled="disabled"><span><input type="text" placeholder="Answer here"></span></li>'; 
                    }
                answers += '</ul>';
                 
                content += answers;
                
                // finally append to dom
                $(this).parent().parent().append(content);
                break;               
        default:
                $(this).parent().append('Something went wrong! =(');
    }
    
    // increment question counter
    parentForQCounter.find('.survey_questions_counter').val(parseInt(parentForQCounter.find('.survey_questions_counter').val())+1);
    
    // remove survey control from dom
    $(this).parent().remove();
    
    return false;
});

$('#content_appears_here').on('click', '.btnRemoveQuestion', function(e){
    e.preventDefault();
    
    var parentForQCounter = $(this).parent().parent().parent().parent();
    // remove question
    $(this).parent().remove();
    // update question counter 
    parentForQCounter.find('.survey_questions_counter').val(parseInt(parentForQCounter.find('.survey_questions_counter').val())-1);
                                            //.find('.survey_question_number')
    //$(this).parent().parent().parent().html();
    
});

/* ---------------------------------- */