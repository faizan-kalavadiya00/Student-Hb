(() => {
"use strict";
const $ = id => document.getElementById(id);
const api = "pr13_api.php";
let csrf = "", resource = "students", records = [], editingId = null, debounceTimer = null;
const message = (text, type="info") => { const box=$("pr13-message"); box.textContent=text; box.className="pr13-message "+type; box.hidden=false; };
const esc = value => String(value ?? "").replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const fields = {
 students: [
  {key:"enrollment_no",label:"Enrollment number",max:20,required:true},
  {key:"full_name",label:"Full name",max:100,required:true},
  {key:"email",label:"Email",type:"email",max:150,required:true},
  {key:"course",label:"Course",max:100,required:true},
  {key:"study_year",label:"Study year",type:"number",min:1,max:6,required:true}
 ],
 events: [
  {key:"title",label:"Event title",max:150,required:true},
  {key:"category",label:"Category",max:80,required:true},
  {key:"venue",label:"Venue",max:180,required:true},
  {key:"event_date",label:"Date and time",type:"datetime-local",required:true},
  {key:"status",label:"Status",type:"select",options:[["open","Open"],["closed","Closed"]],required:true},
  {key:"description",label:"Description",type:"textarea",max:5000}
 ]
};
function renderForm(record=null) {
 const defs=fields[resource]; $("pr13-fields").innerHTML=defs.map(f=>{
  const value=record ? (record[f.key] ?? "") : (f.key==="status"?"open":"");
  let control;
  if(f.type==="select") control=`<select id="field-${f.key}" name="${f.key}" ${f.required?"required":""}>${f.options.map(o=>`<option value="${o[0]}" ${String(value)===o[0]?"selected":""}>${o[1]}</option>`).join("")}</select>`;
  else if(f.type==="textarea") control=`<textarea id="field-${f.key}" name="${f.key}" maxlength="${f.max}" ${f.required?"required":""}>${esc(value)}</textarea>`;
  else control=`<input id="field-${f.key}" name="${f.key}" type="${f.type||"text"}" value="${esc(f.type==="datetime-local" ? String(value).replace(" ","T").slice(0,16):value)}" ${f.max?`maxlength="${f.max}"`:""} ${f.min?`min="${f.min}"`:""} ${f.max&&f.type==="number"?`max="${f.max}"`:""} ${f.required?"required":""}>`;
  return `<label>${f.label}${control}</label>`;
 }).join("");
 $("pr13-id").value=record?.id ?? "";
 editingId=record?.id ?? null;
 $("pr13-form-title").textContent=(editingId?"Edit ":"Add ")+(resource==="students"?"student":"event");
 $("pr13-save").textContent=editingId?"Update record":"Save record";
 $("pr13-cancel").hidden=!editingId;
}
function dateLabel(v){ if(!v)return ""; const d=new Date(String(v).replace(" ","T")); return Number.isNaN(d.getTime())?String(v):d.toLocaleString(); }
function renderTable() {
 const isStudent=resource==="students";
 const cols=isStudent?[["enrollment_no","Enrollment"],["full_name","Name"],["email","Email"],["course","Course"],["study_year","Year"]]:[["title","Title"],["category","Category"],["venue","Venue"],["event_date","Date"],["status","Status"]];
 $("pr13-head").innerHTML=`<tr>${cols.map(c=>`<th>${c[1]}</th>`).join("")}<th>Actions</th></tr>`;
 $("pr13-body").innerHTML=records.length?records.map(r=>`<tr>${cols.map(c=>`<td>${esc(c[0]==="event_date"?dateLabel(r[c[0]]):r[c[0]])}</td>`).join("")}<td><div class="pr13-actions"><button type="button" data-edit="${r.id}">Edit</button><button type="button" class="delete" data-delete="${r.id}">Delete</button></div></td></tr>`).join(""):`<tr><td colspan="${cols.length+1}">No records found.</td></tr>`;
 $("pr13-count").textContent=`${records.length} record(s) shown (maximum 200 per request).`;
 $("pr13-list-title").textContent=resource==="students"?"Students":"Events";
}
async function request(url, options={}) {
 const response=await fetch(url,{credentials:"same-origin",...options,headers:{"Accept":"application/json",...(options.body?{"Content-Type":"application/json"}:{}),...(options.headers||{})}});
 let data; try{data=await response.json()}catch{throw new Error("Server returned an invalid response. Check PHP errors and the API endpoint.");}
 if(!response.ok||!data.success) throw new Error(data.error||`Request failed (${response.status}).`);
 return data;
}
async function load() {
 message("Loading records…","info");
 try {
  const q=new URLSearchParams({resource,search:$("pr13-search").value.trim()});
  const data=await request(`${api}?${q}`);
  csrf=data.csrf; records=data.data; renderTable(); message(`Loaded ${records.length} ${resource} record(s).`,"success");
 } catch(e) { message(e.message,"error"); records=[]; renderTable(); }
}
function formData(){ const data={}; fields[resource].forEach(f=>{const el=$(`field-${f.key}`);data[f.key]=el.value.trim();});return data; }
$("pr13-form").addEventListener("submit",async e=>{
 e.preventDefault(); const data=formData(); data.action=editingId?"update":"create"; if(editingId)data.id=editingId;
 $("pr13-save").disabled=true; message(editingId?"Updating record…":"Creating record…","info");
 try { const result=await request(`${api}?resource=${resource}`,{method:"POST",headers:{"X-CSRF-Token":csrf},body:JSON.stringify(data)}); message(result.message,"success"); renderForm(); await load(); }
 catch(err){message(err.message,"error");} finally{$("pr13-save").disabled=false;}
});
$("pr13-body").addEventListener("click",async e=>{
 const edit=e.target.closest("[data-edit]"), del=e.target.closest("[data-delete]");
 if(edit){const record=records.find(r=>String(r.id)===edit.dataset.edit);if(record){renderForm(record);$("pr13-form").scrollIntoView({behavior:"smooth",block:"start"});}return;}
 if(del){const id=Number(del.dataset.delete);if(!Number.isInteger(id)||id<1)return;if(!confirm("Delete this record? Student records are soft-deleted; event records are permanently deleted."))return;
  message("Deleting record…","info");
  try{const result=await request(`${api}?resource=${resource}`,{method:"POST",headers:{"X-CSRF-Token":csrf},body:JSON.stringify({action:"delete",id})});if(editingId===id)renderForm();message(result.message,"success");await load();}
  catch(err){message(err.message,"error");}
 }
});
$("pr13-resource").addEventListener("change",()=>{resource=$("pr13-resource").value;$("pr13-search").value="";renderForm();load();});
$("pr13-search").addEventListener("input",()=>{clearTimeout(debounceTimer);debounceTimer=setTimeout(load,300);});
$("pr13-refresh").addEventListener("click",load);
$("pr13-cancel").addEventListener("click",()=>renderForm());
renderForm(); load();
})();