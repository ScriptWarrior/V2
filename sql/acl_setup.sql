-- universal content mod
-- GID 0 - no grop (not logged), same with UID = 0
-- no root account at all, just 'roles'
-- first dummy modules
insert into acl (mod_name,action,schema_name,logging_system,acl_res_id, acl_uid, acl_gid,csrf_protect) VALUES ('error','','',0,0,0,0,0); 
insert into acl (mod_name,action,schema_name,logging_system,acl_res_id, acl_uid, acl_gid,csrf_protect) VALUES ('file_download','','file_published',0,0,0,0,0);
insert into acl (mod_name,action,schema_name,logging_system,acl_res_id, acl_uid, acl_gid,csrf_protect) VALUES
('content','update','content',0,0,0,0,1), -- per owner
('content','create','',0,0,0,31337,1), -- only my group ;D
('content','delete','content',0,0,0,0,1), -- per owner
('content','','',0,0,0,0,0), -- everyone
('content','list','',0,0,0,0,0); -- everyone

-- account
insert into acl (mod_name,action,schema_name,logging_system,acl_res_id, acl_uid, acl_gid,csrf_protect) VALUES
('account','log_in','',1,0,0,0,1),
('account','log_out','',0,0,0,0,1),
('account','register','',0,0,0,0,1),
('account','','',0,0,0,0,0),
('account','show_me','',0,0,0,0,0),
('account','show','user',0,0,0,0,0),
('account','register_form','',0,0,0,0,0),
('account','update','user',0,0,0,0,1);	-- private method