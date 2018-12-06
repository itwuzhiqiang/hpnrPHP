module.exports = class {

	onDataLoad(props) {
		return {
			accidentDetail: this.dataQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			evidences: [],
			evidence0: [],
			evidence1: [],
			evidence2: [],
			evidence3: [],
			evidence4: [],
			evidence5: [],
			evidence6: [],
			evidence_1: [],
			evidence_2: [],
			evidence_3: [],
			showevidence0: false,
			showevidence1: false,
			showevidence2: false,
			showevidence3: false,
			showevidence4: false,
			showevidence5: false,
			showevidence6: false,
			showevidence_1: false,
			showevidence_2: false,
			showevidence_3: false,
			imgs: [],
			otherImgs: [],
			videoImg: [],
		};
	}

	onLoaded() {
		this.state.accidentDetail.fetch((data) => {
			let imgs = [];
			let videoImg = '';
			if (data && data.evidences) {
				_.map(data.evidences.reverse(), (item) => {
					if (item.aeFileLabel == -2) {
						videoImg = item.aeFileThumbnail;
					} else {
						imgs.push({
							'aeFileLabel': item.aeFileLabel,
							'src': item.aeFileAddress,
							'text': item.aeFileDescription,
						})
					}
				});
				this.setState({
					imgs: imgs,
					videoImg: videoImg,
				}, () => {
					console.log(this.state.imgs)
					console.log(this.state.otherImgs)
					console.log(this.state.videoImg)
				})
			}
		});



		function showImg() {
			var ImgsTObj = $('.carp');//class=EnlargePhoto的都是需要放大的图像
			if(ImgsTObj){
				console.log(ImgsTObj);
				$.each(ImgsTObj,function(){
					$(this).click(function(){
						var currImg = $(this);
						$('<view class="TempContainer row ac"></view>').appendTo("body");
						$('.TempContainer').html('<image class="showimg" src=' + currImg.attr('src') + '/>');
						if($('.showimg').width()>$(window).width()){
							$('.showimg').css('width','100%');
						}
						if($('.showimg').height()>$(window).height()){
							$('.showimg').css('height','100%');
						}
						$('.TempContainer').click(function(){
							$(this).remove();
						});
					});
				});
			}
			else{
				return false;
			}
		}
		setTimeout(showImg,2000);


		// this.state.accidentDetail.fetch((json) => {
		// 	if (json) {
		// 		this.setState({
		// 			evidences: json.evidences,
		// 		});
		// 		let value=[];
		// 		for(let index in json.evidences){
		// 			value=json.evidences[index]
		// 			if(value.aeFileLabel==0){
		// 				this.setState({
		// 					evidence0: value,
		// 					showevidence0:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==1){
		// 				this.setState({
		// 					evidence1: value,
		// 					showevidence1:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==2){
		// 				this.setState({
		// 					evidence2: value,
		// 					showevidence2:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==3){
		// 				this.setState({
		// 					evidence3: value,
		// 					showevidence3:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==4){
		// 				this.setState({
		// 					evidence4: value,
		// 					showevidence4:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==5){
		// 				this.setState({
		// 					evidence5: value,
		// 					showevidence5:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==6){
		// 				this.setState({
		// 					evidence6: value,
		// 					showevidence6:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==-1){
		// 				this.setState({
		// 					evidence_1: value,
		// 					showevidence_1:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==-2){
		// 				this.setState({
		// 					evidence_2: value,
		// 					showevidence_2:true,
		// 				});
		// 			}
		// 			if(value.aeFileLabel==-3){
		// 				this.setState({
		// 					evidence_3: value,
		// 					showevidence_3:true,
		// 				});
		// 			}
		// 		}
		// 	}
		// });


	}

	goHistory() {
		window.history.go(-1);
	}


}