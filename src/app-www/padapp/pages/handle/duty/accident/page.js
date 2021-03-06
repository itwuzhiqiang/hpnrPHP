module.exports = class {

	onDataLoad() {
		return {
			handleAccidentType: this.fetchQL('handleAccidentType'),
		};
	}

	onLoaded() {
        // let currenturl = window.location.href;
        // alert(currenturl);
        // $.ajax({
        //     url:that.state.dataServer + '/accident/v1/app/accident/history/addXjHistoryRecord?accountToken=' + that.state.policeInfo.data.sign,
        //     type:'POST',
        //     data: currenturl,
        //     cache: false,
        //     processData: false,
        //     contentType: false
        // }).done((res)=>{
        //     console.log("成功");
        // }).file((ress)=>{
        //     console.log("失败");
        // })

    }

	goDetail(item) {
		padapp.navigate.push({
			url: '/handle/duty/detail',
			params: {
				data: item,
			}
		})
	}

	goBack() {
		padapp.navigate.pop();
	}

	goChoose(item) {
		padapp.app.emit('/handle.duty.accident', {
			data: item,
		});
		padapp.navigate.pop();
	}

}