module.exports = class {

	onDataLoad(props) {
		return {
			handleMoveCare: this.dataQL('handleMoveCare'),
			rpaId: props.rpaId,
			type: props.type,
            dataServer: padapp.app.getConfig('rpapi'),
            policeInfo: this.fetchQL('policeInfo'),
		};
	}

	goDesc() {
		this.state.handleMoveCare.setParam('rpaId',this.state.rpaId).fetch((json)=>{
			padapp.navigate.linkTo({
				url: '/handle.desc',
				params: {
					rpaId: this.state.rpaId,
					type: this.state.type,
				}
			})
		});
	}

	goHistory() {
		window.history.go(-1);
	}
    onLoaded() {
        let that = this;
        let currenturl = window.location.href;
        $.ajax({
            url:that.state.dataServer + '/accident/v1/app/accident/history/addMHistoryRecord?html=' + currenturl + '&accountToken='  + that.state.policeInfo.data.sign,
            type:'POST',
            data: currenturl,
            cache: false,
            processData: false,
            contentType: false
        }).done((res)=>{
            console.log("success");
        }).fail(function (ress) {
            console.log("fail");

        })

    }
}