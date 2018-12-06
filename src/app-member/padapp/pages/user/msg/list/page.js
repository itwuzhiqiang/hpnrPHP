module.exports = class {

	onDataLoad() {
		return {
			eventMsg: this.fetchQL('eventMsg'),
			msgQueryByParentId: this.fetchQL('msgQueryByParentId'),
			nav: 1,
			eventMsgNextPage: false,
			msgQueryByParentIdNextPage: false,
			eventMsgPageNum: 1,
			msgQueryByParentIdPageNum: 1,
			userInfo: this.fetchQL('userInfo'),
		};
	}

	onLoaded() {

		let code = this.state.userInfo.data.code;
		if (code === -1) {
			this.overlay.msg('登录失效');
			padapp.navigate.linkTo({
				url: '/login',
			})
		}

		let eventMsgNextPage = this.state.eventMsg.data.hasNextPage;
		let eventMsgPageNum = this.state.eventMsg.data.pageNum;
		let msgQueryByParentIdNextPage = this.state.msgQueryByParentId.data.hasNextPage;
		let msgQueryByParentIdPageNum = this.state.msgQueryByParentId.data.pageNum;
		this.setState({
			eventMsgNextPage: eventMsgNextPage,
			msgQueryByParentIdNextPage: msgQueryByParentIdNextPage,
			eventMsgPageNum: eventMsgPageNum,
			msgQueryByParentIdPageNum: msgQueryByParentIdPageNum,
		})
	}


	goBack() {
		window.history.go(-1);
	}

	nextPage() {
		let nav = this.state.nav;
		if (nav === 1) {
			this.state.eventMsg.setParam('pageNum',this.state.eventMsgPageNum + 1).fetch((data)=>{
				console.log(data);
				this.setState({
					eventMsgNextPage: data.hasNextPage,
					eventMsgPageNum: data.pageNum,
				})
			})
		}else{
			this.state.msgQueryByParentId.setParam('pageNum',this.state.msgQueryByParentIdPageNum + 1).fetch((data)=>{
				console.log(data);
				this.setState({
					msgQueryByParentIdNextPage: data.hasNextPage,
					msgQueryByParentIdPageNum: data.pageNum,
				})
			})
		}
	}

	setNav(type) {
		this.setState({
			nav: type,
		})
	}

	goNotice(typeId, typeName) {
		padapp.navigate.push({
			url: '/user/msg/notice',
			params: {
				typeId: typeId,
				typeName: typeName,
			}
		})
	}

}