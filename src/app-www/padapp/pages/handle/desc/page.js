module.exports = class {

	onDataLoad(props) {
		return {
			dataServer: padapp.app.getConfig('rpapi'),
			show: false,
			rpaId: props.rpaId,
			type: props.type,
			address: '',
			addressDesc: '',
			desc: '',
			str: '格式:xx高速xxxx公里xxx米XXX行方向XXX',
			policeInfo: this.fetchQL('policeInfo'),
			handleRoad: this.fetchQL('handleRoad'),
			time: padapp.string.date('Y-m-d H:i:s'),
			startPoint: {},
			handleAccident: this.fetchQL('handleAccident', {
				rpaId: props.rpaId,
			}),
			addressDescType: 1,
			chedaoData: ['请选择', '第一行车道', '第二行车道', '第三行车道', '第四行车道', '应急车道'],
			dlhjData: ['请选择', '收费站', '服务区', '桥梁', '隧道', '岔道', '匝道', '施工路段', '管制路段', '普通路段', '其它'],//道路环境
			dlxxData: ['请选择', '平直', '上坡', '下坡', '弯道', '弯坡', '其它'],//道路线行
			roadData: [],//高速路
			gsgl: 0,
			glfx: 1,
			zh_km: '',
			zh_m: '',
			lijiao1: '',
			lijiao2: '',
			address1: '',
			chedao: 1,
			dlhj: 9,
			dlxx: 1,
		};
	}

	onLoad() {
		this.addListener('/handle.duty.accident', (data) => {
			this.setState({
				address: data.val,
			})
		});
		this.addListener('/handle.desc.map', (data) => {
			console.log(data);
			if (data) {
				let str = '';
				if (data.data.address.street) {
					str = data.data.address.city + data.data.address.district + data.data.address.street + data.data.address.streetNumber;
				} else {
					str = data.data.address.city + data.data.address.district + data.data.address.business;
				}
				this.setState({
					startPoint: data.data.point,
					address: str,
				})
			}
		});
	}

	onLoaded() {
		let that = this;
		let myDate = new Date();
		let y = myDate.getFullYear();
		let m = myDate.getMonth() + 1;
		let d = myDate.getDate();
		let h = myDate.getHours();
		let f = myDate.getMinutes();
		let s = myDate.getSeconds();
		let startPoint = {};
		if (this.state.handleRoad && this.state.handleRoad.data) {
			console.log(this.state.handleRoad.data)
			this.setState({
				roadData: this.state.handleRoad.data,
				gsgl: this.state.handleRoad.data[0]['roadid'],
			})
		}


		new DateSelector({
			input: 'date-selector-input',//点击触发插件的input框的id
			container: 'targetContainer',//插件插入的容器id
			type: 0,
			//0：不需要tab切换，自定义滑动内容，建议小于三个；
			//1：需要tab切换，【年月日】【时分】完全展示，固定死，可设置开始年份和结束年份
			param: [1, 1, 1, 1, 1],
			//设置['year','month','day','hour','minute'],1为需要，0为不需要,需要连续的1
			beginTime: [2017, 5, 7, 1, 1],//如空数组默认设置成1970年1月1日0时0分开始，如需要设置开始时间点，数组的值对应param参数的对应值。
			endTime: [],//如空数组默认设置成次年12月31日23时59分结束，如需要设置结束时间点，数组的值对应param参数的对应值。
			recentTime: [y, m, d, h, f],//如不需要设置当前时间，被为空数组，如需要设置的开始的时间点，数组的值对应param参数的对应值。
			success: function (arr, arr2) {
				that.setState({
					time: arr[0] + '-' + that.changeTime(arr[1]) + '-' + that.changeTime(arr[2]) + ' ' + that.changeTime(arr[3])
					+ ':' + that.changeTime(arr[4]) + ':' + that.changeTime(s),
				});
			}
		});
		let mapObj, geolocation;
		//加载地图，调用浏览器定位服务
		mapObj = new AMap.Map('iCenter');
		mapObj.plugin('AMap.Geolocation', function () {
			geolocation = new AMap.Geolocation({
				enableHighAccuracy: true,//是否使用高精度定位，默认:true
				timeout: 10000,          //超过10秒后停止定位，默认：无穷大
				maximumAge: 0,           //定位结果缓存0毫秒，默认：0
				convert: true,           //自动偏移坐标，偏移后的坐标为高德坐标，默认：true
				showButton: false,        //显示定位按钮，默认：true
				buttonPosition: 'LB',    //定位按钮停靠位置，默认：'LB'，左下角
				buttonOffset: new AMap.Pixel(10, 20),//定位按钮与设置的停靠位置的偏移量，默认：Pixel(10, 20)
				showMarker: false,        //定位成功后在定位到的位置显示点标记，默认：true
				showCircle: false,        //定位成功后用圆圈表示定位精度范围，默认：true
				panToLocation: false,     //定位成功后将定位到的位置作为地图中心点，默认：true
				zoomToAccuracy: false      //定位成功后调整地图视野范围使定位位置及精度范围视野内可见，默认：false
			});
			mapObj.addControl(geolocation);
			geolocation.getCurrentPosition();
			AMap.event.addListener(geolocation, 'complete', (data) => {
				startPoint['lng'] = data.position.getLng();
				startPoint['lat'] = data.position.getLat();
				let ggPoint = new BMap.Point(data.position.getLng(), data.position.getLat());
				let convertor = new BMap.Convertor();//百度坐标转换
				let pointArr = [];
				pointArr.push(ggPoint);
				convertor.translate(pointArr, 3, 5, (bdata) => {
					that.setState({
						startPoint: bdata.points[0],
						address: data.formattedAddress,
					})
				});
			});//返回定位信息
			AMap.event.addListener(geolocation, 'error', () => {
				that.overlay.msg('定位失败,请使用地图定位')
			});      //返回定位出错信息
		});

		// let obj = {};
		// if (navigator.geolocation) {
		// 	navigator.geolocation.getCurrentPosition(showPosition, showError);
		// 	function showPosition(position) {
		// 		let ggPoint = new BMap.Point(position.coords.longitude, position.coords.latitude);
		// 		let convertor = new BMap.Convertor();//百度坐标转换
		// 		let pointArr = [];
		// 		pointArr.push(ggPoint);
		// 		convertor.translate(pointArr, 1, 5, (bdata) => {
		// 			that.setState({
		// 				startPoint: bdata.points[0],
		// 			})
		// 		});
		// 	}
		// } else {
		// 	alert("不支持定位");
		// }
		//
		//
		// function showError(error) {
		// 	switch (error.code) {
		// 		case error.PERMISSION_DENIED:
		// 			alert("没有权限");
		// 			break;
		// 		case error.POSITION_UNAVAILABLE:
		// 			alert("位置信息不可用");
		// 			break;
		// 		case error.TIMEOUT:
		// 			alert("获取超时")
		// 			break;
		// 		case error.UNKNOWN_ERROR:
		// 			alert("未知错误");
		// 			break;
		// 	}
		// }


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
		this.setShow();
		padapp.navigate.push({
			url: '/handle/desc/update',
			params: {
				address: this.state.address,
			}
		})
	}

	setVal(e, type) {
		let val = e.target.value;
		if (type == 1) {
			this.setState({
				addressDescType: val,
			})
		}
		if (type == 3) {
			this.setState({
				gsgl: val,
			})
		}
		if (type == 4) {
			this.setState({
				glfx: val,
			})
		}
		if (type == 5) {
			this.setState({
				lijiao1: val,
			})
		}
		if (type == 6) {
			this.setState({
				lijiao2: val,
			})
		}
		if (type == 7) {
			this.setState({
				zh_km: val,
			})
		}
		if (type == 8) {
			this.setState({
				zh_m: val,
			})
		}
		if (type == 9) {
			this.setState({
				address1: val,
			})
		}
		if (type == 2) {
			let len = val.length;
			if (len <= 300) {
				this.setState({
					desc: val,
				})
			} else {
				this.overlay.msg('请简要描述(300字内)')
			}
		}
	}

	setVal1(e, type) {
		let val = e.target.value;
		if (type == 1) {
			this.setState({
				chedao: val,
			})
		}
		if (type == 2) {
			this.setState({
				dlhj: val,
			})
		}
		if (type == 3) {
			this.setState({
				dlxx: val,
			})
		}
	}

	goInformation() {
		let that = this;
		let time = this.state.time;
		let addressDescType = this.state.addressDescType;
		let gsgl = this.state.gsgl;
		let glfx = this.state.glfx;
		let zh_km = this.state.zh_km;
		let zh_m = this.state.zh_m;
		let chedao = this.state.chedao;
		let dlhj = this.state.dlhj;
		let dlxx = this.state.dlxx;
		let reg = /^\d+(\.\d{1,2})?$/;
		let address = addressDescType == 1 ? this.state.address + this.state.lijiao1 + '立交至' + this.state.lijiao2 + '立交'
			: this.state.address;
		let desc = this.state.desc;
		if (!time) {
			this.overlay.msg('请选择时间');
			return;
		}
		if (!address) {
			this.overlay.msg('请填写地址');
			return;
		}

		if (addressDescType == 1) {
			if (!gsgl) {
				this.overlay.msg('请选择高速公路');
				return;
			}
			if (!glfx) {
				this.overlay.msg('请选择上下行');
				return;
			}
			if (this.state.lijiao1) {
				this.overlay.msg('填写起始立交格式错误');
				return;
			}
			if (this.state.lijiao2) {
				this.overlay.msg('填写终止立交格式错误');
				return;
			}
			if (!zh_km || !reg.test(zh_km)) {
				this.overlay.msg('请填写正确的桩号千米(数字,最多包含两位小数)');
				return;
			}
			if (!zh_m || !reg.test(zh_m)) {
				this.overlay.msg('请填写正确的桩号米(数字,最多包含两位小数)');
				return;
			}
		}

		if (!chedao) {
			this.overlay.msg('请选择车道');
			return;
		}
		if (!dlhj) {
			this.overlay.msg('请选择道路环境');
			return;
		}
		if (!dlxx) {
			this.overlay.msg('请选择道路线行');
			return;
		}

		// if (!desc) {
		// 	this.overlay.msg('请填写描述');
		// 	return;
		// }
		let url = that.state.dataServer + '/accident/v2/app/accident/situation?accountToken=' + this.state.policeInfo.data.sign
			+ '&rpaOccurrenceTime=' + time + '&rpaId=' + this.state.rpaId + '&rpaAddress=' + encodeURIComponent(address)
			+ '&rpaLongitude=' + this.state.startPoint.lng + '&rpaLatitude=' + this.state.startPoint.lat + '&coordsys=baidu'
			+ '&rpaDescribe=' + encodeURIComponent(desc) + '&sfzwz=' + addressDescType + '&chedao=' + chedao
			+ '&dlhj=' + dlhj + '&dlxx=' + dlxx;
		if (addressDescType == 1) {
			url = url + '&gsgl=' + gsgl + '&glfx=' + glfx
				+ '&zh_km=' + zh_km + '&zh_m=' + zh_m
		}

		// console.log(url);return;

		//音频文件上传
		// let formData = new FormData();
		//
		// let files = this.page.refs.file.files;
		// for (let k = 0; k < files.length; k++) {
		// 	formData.append('file', files[k]);
		// }
		//
		// console.log(files[0])


		$.ajax({
			url: url,
			type: 'POST',
			cache: false,
			// data: formData,
			processData: false,
			contentType: false
		}).done((res) => {
			if (res) {
				if(res.msg){
					this.overlay.msg(res.msg);
				}
				if (res.code == 0) {
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