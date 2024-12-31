// JavaScript Document
		jumpprocess = function(a) {//loading动画
			if(a == 's'){
				$('body').append('<div class="j_loading" style="/* display: none; */"></div>');
			}else{
				$('.j_loading').remove();
			}
		};
        
function checkpost(obj){	
	if(cate==1 && obj.p_type!=null && obj.p_type.value==0){
	UC.showTips('没有选择主题分类',2000);
		return false;
	}
	if(obj.atc_title.value==""){
		if (action != 'reply'){
	UC.showTips('<font color=\'red\'>标题</font> 不能为空',2000);
			return false;
		}
	} else if(strlen(obj.atc_title.value)>STATFROM['atc_title']){
	UC.showTips('标题超过最大长度'+$db_titlemax+'字节',2000);
		return false;
	}
	if(strlen(obj.atc_content.value)<2){
		UC.showTips('内容少于2字节',2000);
		return false;
	} else if(strlen(obj.atc_content.value)>STATFROM['atc_content']){
	UC.showTips('文章内容大于'+$db_postmax+'字节',2000);
		return false;
	}
	
	$(".sub-release").text('发布中').css({
		color: "#999"
	});
	$(".essay-btn").text('发布中').removeClass('on');
	
	obj.Submit.disabled = true;
		obj['type'].value = "ajax_addfloor";
		postfloor(obj);
	return false;
}
function postfloor(obj){
	jumpprocess('s');
	obj.Submit.disabled = true;
	ajax.send(postfloor_url, obj, function() {
		var changeCheck = true;
		var rText = ajax.runscript(ajax.request.responseText);
		//===========================自定义提示特效
		if (rText.indexOf('read_t') !== -1) {
			var div=document.createElement("div");
			div.innerHTML=rText;
			
			//touch原内容
			if ($('.reviewList').length > 0) {
            $('.reviewList').append(rText);
            } else {
            $('.fastpost-area').before('<ul class="review-list reviewList">' + rText + '</ul>');
            }
			//touch原内容
			$(".fastpost-mask").addClass("fx-none");//关闭弹出的回复框
			//$.saveUserData('forum_' + discuz_uid, '');//清空存储的内容
			$('#e_textarea').val('');
			$('#imglist').children('li:not([class])').remove();
			$(".upFileBox").addClass("fx-none");
			$(".upPicBox").addClass("fx-none");
			$(".upFaceBox").addClass("fx-none");
			$(".upFaceBox").siblings(".triangle-cel").removeClass("on");
			//$('#imglist') && $('#imglist').html('<li class="last"><input type="file" name="Filedata" id="filedata" accept="image/*"></li>');
			//$('.read_t').scrollToID('.read_t')
			UC.showTips('提交成功',2000);
			var id = $('.review-list li').last().attr("id"), X = $('#'+id).offset().top -40;
			window.scrollTo(0, X)
		} else {
			var rText = ajax.request.responseText.split('\t');
			
			if (rText[0] == 'success') {
			
			$(".fastpost-mask").addClass("fx-none");//关闭弹出的回复框
			//$.saveUserData('forum_' + discuz_uid, '');//清空存储的内容
			$('#e_textarea').val('');
			$('#imglist').children('li:not([class])').remove();
			$(".upFileBox").addClass("fx-none");
			$(".upPicBox").addClass("fx-none");
			$(".upFaceBox").addClass("fx-none");
			$(".upFaceBox").siblings(".triangle-cel").removeClass("on");
			//$('#imglist') && $('#imglist').html('<li class="last"><input type="file" name="Filedata" id="filedata" accept="image/*"></li>');
			UC.showTips('提交成功，等待审核',2000);
			
			}else if (rText[0] == 'continue') {
			
			UC.showTips(rText[1],2000);
			obj.iscontinue.value = 1;
			postfloor(obj);
			
			} else {
			UC.showDialog({
			type: 'info',//error,right
			title: rText[1],
            message: rText[0]
            });
			}
			
			$('.essay-btn').text('发布').addClass('on');
            $('.sub-release').text('发布').css({
			color: "#f60"
			});
		}
		obj.Submit.disabled = false;
		jumpprocess();
		$("#changeGdCode_a").click();//重置验证码
		return false;
	});
}
function postlzl(obj){
		jumpprocess('s');
		//obj.Submit.disabled = true;
		//obj.iscontinue.value = 1;
		    var pid = 0,touid = '';
            if ($(event.target).closest('li').attr('id') != '') {
                isMainReplyBtn = true;
                pid = $(event.target).closest('li').attr('id').replace('pid', '');
            } else {
                pid = $(event.target).closest('ul').closest('li').attr('id').replace('pid', '');
                touid = $(event.target).attr('data-tid');
            }
			alert(obj.pid);return false;

	ajax.send(postfloor_url+'&action=quote&article=1&tid='+ tid +'&pid='+pid, obj, function(){
		var rText = ajax.request.responseText.split('\t');
		if (rText[0] == 'success') {
		//发布成功！

		var message = $('#commentmessage').val();
		if(obj.iscontinue.value == 1){
		message = '<span style="color:black;background-color:#ffff66">请等待管理员审核!</span>';
		}
		var mainReply = $('#pid' + pid + '');
		var reviewlist = mainReply.children('.sub-review-list');
		var review = '<li><section class=review-t><div class=review-name><span><a href=u.php>我</a>: </span>'+ message +'</div></section><div class=review-way arrowVoice><span class=review-time><span>刚刚</span></span><div class=review-sbox><div class=host-edit></div><span href=javascript:void(0) data-pos=logo_bbsdetail-3278_bottom_po-1_other-review data-ac=review-47110090 class=btn-cblack><i class=voice-g></i></span></div></div></li>';
			if (reviewlist.length == 0) {
			mainReply.children('.review-way').last().after('<ul class="sub-review-list">' + review + '</ul>');//添加<ul>内容
			} else {		
				if (reviewlist.children().last().children('.data-load').length == 0) {
				reviewlist.append(review);//添加li内容
				} else {
				reviewlist.children().last().children('.data-load').before(review);
				}
			}		
		$('#commentmessage').val('');
		$('#commenteditor').addClass('fx-none');//关闭对话回复框
		var plzltitle = obj.iscontinue.value == 1 ? '回复成功，请等待管理员审核' : '提交成功' ;
		UC.showTips( plzltitle ,2000);
		}else if (rText[0] == 'continue') {
		
			UC.showDialog({
			title: '确定要回复请再次点击回复按钮',
            message: rText[1],
            type: 'info'
            });
			obj.iscontinue.value = 1;
			
		} else {
			UC.showDialog({
            message: rText[0],
            type: 'info'
            });
		}
		obj.Submit.disabled = false;
		$("#changeGdCode_b").click();//重置验证码
		jumpprocess('e');	
		return false;
	});
}
