(() => {
  const {registerBlockType} = wp.blocks;
  const {createElement:h, Fragment} = wp.element;
  const {InspectorControls} = wp.blockEditor;
  const {PanelBody, TextControl, TextareaControl} = wp.components;
  const preview = wp.serverSideRender.default || wp.serverSideRender;
  ['header','hero','press','categories','rail','collections','verse','custom-steps','reviews','founder','signup','footer','archive','product','story','custom','wishlist','contact','cart','checkout'].forEach(name => registerBlockType(`seedprints/${name}`, {
    apiVersion:3, title:`Seed Prints · ${name.replaceAll('-',' ')}`, icon:'store', category:'design',
    attributes:{heading:{type:'string',default:''},body:{type:'string',default:''},reference:{type:'string',default:''},imageId:{type:'number',default:0}},
    edit:({attributes:a,setAttributes:set}) => h(Fragment,{},h(InspectorControls,{},h(PanelBody,{title:'Section content'},h(TextControl,{label:'Heading',value:a.heading,onChange:heading=>set({heading})}),h(TextareaControl,{label:'Body or verse',value:a.body,onChange:body=>set({body})}),h(TextControl,{label:'Verse reference',value:a.reference,onChange:reference=>set({reference})}),h(TextControl,{label:'Image attachment ID',type:'number',value:a.imageId,onChange:id=>set({imageId:Number(id)||0})}))),h(preview,{block:`seedprints/${name}`,attributes:a})),
    save:()=>null
  }));
})();
