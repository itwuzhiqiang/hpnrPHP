module.exports = class {

	onDataLoad(props) {
		return {
			handlePlateType: this.fetchQL('handlePlateType'),
		};
	}

	onLoaded() {
		console.log(789);
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


	goBack() {
		padapp.navigate.pop();
	}

	goChoose(item) {
		padapp.app.emit('/handle.information.list', {
			data: item,
		});
		padapp.navigate.pop();
	}

}