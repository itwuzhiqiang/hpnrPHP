module.exports = class {

	onDataLoad(props) {
		return {
			dataServer: padapp.app.getConfig('rpapi'),
			show: false,
			rpaId: props.rpaId,
			type: props.type,
			address: '',
			desc: '',
			userInfo: this.fetchQL('userInfo'),
			time: padapp.string.date('Y-m-d H:i:s'),
			startPoint: {},
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			rpaCreateTime: '',
			rpaAddress: '',
			str: '例:重庆市观音桥东环路&立交桥上,请填&后的具体地点',
			symbol: '&',
		};
	}

	onLoad() {
		this.addListener('/handle.duty.accident', (data) => {
			this.setState({
				address: data.address,
			})
		});
		// this.addListener('/handle.desc.map', (data) => {
		// 	console.log(data);
		// 	if (data) {
		// 		let str = '';
		// 		if (data.data.address.street) {
		// 			str = data.data.address.city + data.data.address.district + data.data.address.street + data.data.address.streetNumber;
		// 		} else {
		// 			str = data.data.address.city + data.data.address.district + data.data.address.business;
		// 		}
		// 		this.setState({
		// 			startPoint: data.data.point,
		// 			address: str,
		// 		})
		// 	}
		// });
	}

	onLoaded() {
		let code = this.state.userInfo.data.code;
		if (code === -1) {
			this.overlay.msg('登录失效');
			padapp.navigate.linkTo({
				url: '/login',
				params: {
					popup: true,
				}
			})
		}

		let rpaCreateTime = this.state.handleAccident.data.accident.rpaCreateTime.split('.')[0];
		let rpaAddress = this.state.handleAccident.data.accident.rpaAddress;
		let address = this.state.address;
		let arr = rpaAddress.split('&');
		if (arr.length > 1) {
			address = arr[1]
		}

		this.setState({
			rpaCreateTime: rpaCreateTime,
			rpaAddress: arr[0],
			address: address,
		})

		// let that = this;
		// let myDate = new Date();
		// let y = myDate.getFullYear();
		// let m = myDate.getMonth() + 1;
		// let d = myDate.getDate();
		// let h = myDate.getHours();
		// let f = myDate.getMinutes();
		// let s = myDate.getSeconds();
		// let startPoint = {};
		// new DateSelector({
		// 	input: 'date-selector-input',//点击触发插件的input框的id
		// 	container: 'targetContainer',//插件插入的容器id
		// 	type: 0,
		// 	//0：不需要tab切换，自定义滑动内容，建议小于三个；
		// 	//1：需要tab切换，【年月日】【时分】完全展示，固定死，可设置开始年份和结束年份
		// 	param: [1, 1, 1, 1, 1],
		// 	//设置['year','month','day','hour','minute'],1为需要，0为不需要,需要连续的1
		// 	beginTime: [],//如空数组默认设置成1970年1月1日0时0分开始，如需要设置开始时间点，数组的值对应param参数的对应值。
		// 	endTime: [],//如空数组默认设置成次年12月31日23时59分结束，如需要设置结束时间点，数组的值对应param参数的对应值。
		// 	recentTime: [y, m, d, h, f],//如不需要设置当前时间，被为空数组，如需要设置的开始的时间点，数组的值对应param参数的对应值。
		// 	success: function (arr, arr2) {
		// 		that.setState({
		// 			time: arr[0] + '-' + that.changeTime(arr[1]) + '-' + that.changeTime(arr[2]) + ' ' + that.changeTime(arr[3])
		// 			+ ':' + that.changeTime(arr[4]) + ':' + that.changeTime(s),
		// 		});
		// 	}
		// });
		// let mapObj, geolocation;
		// //加载地图，调用浏览器定位服务
		// mapObj = new AMap.Map('iCenter');
		// mapObj.plugin('AMap.Geolocation', function () {
		// 	geolocation = new AMap.Geolocation({
		// 		enableHighAccuracy: true,//是否使用高精度定位，默认:true
		// 		timeout: 10000,          //超过10秒后停止定位，默认：无穷大
		// 		maximumAge: 0,           //定位结果缓存0毫秒，默认：0
		// 		convert: true,           //自动偏移坐标，偏移后的坐标为高德坐标，默认：true
		// 		showButton: false,        //显示定位按钮，默认：true
		// 		buttonPosition: 'LB',    //定位按钮停靠位置，默认：'LB'，左下角
		// 		buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
		// 		showMarker: false,        //定位成功后在定位到的位置显示点标记，默认：true
		// 		showCircle: false,        //定位成功后用圆圈表示定位精度范围，默认：true
		// 		panToLocation: false,     //定位成功后将定位到的位置作为地图中心点，默认：true
		// 		zoomToAccuracy: false      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
		// 	});
		// 	mapObj.addControl(geolocation);
		// 	geolocation.getCurrentPosition();
		// 	AMap.event.addListener(geolocation, 'complete', (data) => {
		// 		let ggPoint = new BMap.Point(data.position.getLng(), data.position.getLat());
		// 		let convertor = new BMap.Convertor();//百度坐标转换
		// 		let pointArr = [];
		// 		pointArr.push(ggPoint);
		// 		convertor.translate(pointArr, 3, 5, (bdata) => {
		// 			that.setState({
		// 				startPoint: bdata.points[0],
		// 			})
		// 		});
		// 	});//返回定位信息
		// 	AMap.event.addListener(geolocation, 'error', () => {
		// 		that.overlay.msg('定位失败,请使用地图定位')
		// 	});      //返回定位出错信息
		// });

	}

	changeTime(t) {
		let time = 0;
		if (t < 10) {
			time = '0' + t
		} else {
			time = t
		}
		return time
	}

	setShow() {
		this.setState({
			show: !this.state.show,
		})
	}

	goMap() {
		this.setShow();
		padapp.navigate.push({
			url: '/handle/desc/map',
			params: {
				startPoint: this.state.startPoint,
			}
		})
	}

	goUpdate() {
		padapp.navigate.push({
			url: '/handle/desc/update',
			params: {
				rpaAddress: this.state.rpaAddress,
				address: this.state.address,
			}
		})
	}

	setVal(e) {
		let val = e.target.value;
		let len = val.length;
		if (len <= 300) {
			this.setState({
				desc: val,
			})
		} else {
			this.overlay.msg('请简要描述(300字内)')
		}
	}

	goInformation() {
		this.state.userInfo.fetch((json) => {
			if (json.code === -1) {
				padapp.navigate.linkTo({
					url: '/login',
					params: {
						popup: true,
					}
				})
			}
		});

		let that = this;
		let time = this.state.time;
		let address = this.state.address;
		let rpaAddress = address ? this.state.rpaAddress + '&' + address : this.state.rpaAddress;
		let desc = this.state.desc;
		if (!time) {
			this.overlay.msg('请选择时间');
			return;
		}
		if (!address) {
			this.overlay.msg('请填写具体地点');
			return;
		}

		$.ajax({
			url: that.state.dataServer + '/accident/v1/app/accident/situation?accountToken=' + this.state.userInfo.data.account_token
			+ '&rpaId=' + this.state.rpaId + '&rpaAddress=' + encodeURIComponent(rpaAddress)
			+ '&rpaDescribe=' + desc,
			type: 'POST',
			cache: false,
			// data: formData,
			processData: false,
			contentType: false
		}).done((res) => {
			if (res && res.msg) {
				this.overlay.msg(res.msg);
				if (res.msg == '成功') {
					padapp.navigate.linkTo({
						url: '/handle.information',
						params: {
							rpaId: this.state.rpaId,
							type: this.state.type,
						}
					})
				}
			}
		}).fail(function (res) {
			let data = JSON.parse(res.responseText);
			that.overlay.msg(data.msg)
		});
	}

	goHistory() {
		window.history.go(-1);
	}

}