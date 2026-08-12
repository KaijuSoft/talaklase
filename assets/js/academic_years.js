(() => {
  const config = document.getElementById('academicYearsPageConfig');
  const csrfToken = config?.dataset.csrf || '';
  async function post(params) {
    params.append('csrf_token', csrfToken);
    const response = await fetch(window.location.href, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:params});
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Request failed.');
    return data;
  }
  window.saveAcademicYear = async function () {
    const name=document.getElementById('add_ay_name')?.value.trim()||'';
    const start=document.getElementById('add_start_date')?.value||'';
    const end=document.getElementById('add_end_date')?.value||'';
    if(!name){alert('Academic year is required.');return;}
    if(start&&end&&start>end){alert('End date must not be earlier than start date.');return;}
    try{const data=await post(new URLSearchParams({action:'add',ay_name:name,start_date:start,end_date:end}));alert(data.message);location.reload();}catch(e){alert(e.message);}
  };
  window.activateYear = async function (ayId) {
    if(!confirm('Activate this academic year?'))return;
    try{const data=await post(new URLSearchParams({action:'activate',ay_id:String(ayId)}));alert(data.message);location.reload();}catch(e){alert(e.message);}
  };
})();