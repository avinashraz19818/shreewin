import{a2 as k,b,l as g,r as B,B as M,A as P,aX as x,_ as C,N as m,F as o,H as i,O as R,a4 as $,Q as t,R as a,af as y,ag as f}from"./index-_oBi1P6F.js";const w=k({__name:"index",setup(h,{expose:s}){s();const{t:l}=b(),e=g(),r=B([{title:l("betAmounts"),body:[]},{title:l("rewordPercent"),body:[]}]),d=async()=>{const c=await P(x());c&&c.data.map(p=>(r[0].body.push(p.lotteryAmount+""),r[1].body.push(p.exchange_Rate*1e3*100/1e3+"%"),p))};M(()=>{d()});function _(){e.back()}const n={$t:l,router:e,pointRule:r,getProductRules:d,onClick:_,toBet:()=>{sessionStorage.setItem("clickedGameType","lottery"),e.push({path:"/"})}};return Object.defineProperty(n,"__isScriptSetup",{enumerable:!1,value:!0}),n}}),A={class:"pointMall-rule__container content"},N={class:"pointMall-rule__container-pointRule"},S={class:"pointMall-rule__container-pointRule__title"},F={class:"pointMall-rule__container-pointRule__body"},I={class:"toBet"};function V(h,s,l,e,r,d){const _=m("NavBar"),v=m("van-icon");return o(),i("div",A,[R(_,{title:e.$t("pointsRule"),"left-arrow":"",onClickLeft:e.onClick},null,8,["title"]),$(` <div class="pointMall-rule__container-claimRule">
			<div class="pointMall-rule__container-claimRule__title">1.{{ $t('claimPoints') }}</div>
			<div class="pointMall-rule__container-claimRule__body">
				<div>{{ $t('descRules1') }}</div>
				<div>
					<p>{{ $t('inviteFriends') }}</p>
					<p>{{ $t('earnPoints') }}</p>
				</div>
				<div @click="router.push({ path: '/main/InvitationBonus' })">
					<span> {{ $t('toClaim') }} </span>
					<van-icon name="upgrade" />
				</div>
			</div>
		</div> `),t("div",N,[t("div",S,a(e.$t("bonusPoints")),1),t("div",F,[t("div",null,a(e.$t("descRules2")),1),t("div",null,[(o(!0),i(y,null,f(e.pointRule,(n,c)=>(o(),i("div",{key:c},[t("p",null,a(n.title),1),(o(!0),i(y,null,f(n.body,u=>(o(),i("li",{key:u},a(u),1))),128))]))),128))]),t("div",{onClick:s[0]||(s[0]=n=>e.toBet())},[t("span",I,a(e.$t("goBetting")),1),R(v,{name:"upgrade",color:"#D23838"})])])])])}const D=C(w,[["render",V],["__scopeId","data-v-26d63714"],["__file","/home/jenkins/agent/workspace/AR101-Pages-india-shreewin/src/views/activity/PointMall/Rules/index.vue"]]);export{D as default};
