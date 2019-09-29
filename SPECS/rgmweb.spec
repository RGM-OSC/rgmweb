Summary: RGM Web Interface 
Name: rgmweb
Version: 1.0
Release: 24.rgm
Source: %{name}-%{version}.tar.gz
Group: Applications/System
License: GPL
Requires: rgm-base, ged, ged-mysql, lilac, thruk 
Requires: httpd, mariadb-libs, mod_auth_rgm, mod_perl
Requires: php, php-mysql, php-ldap, php-process, php-xml
Requires: nagios >= 3.0, nagvis, nagiosbp, notifier
Requires: net-snmp, nmap-ncat
#Requires: histou, kibana
BuildRequires: rpm-macros-rgm

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

Source1: schema.sql
Source2: httpd-rgmweb.conf
Source3: change_definer.php

# appliance group and users
# /srv/rgm/rgmweb-1.0
%define	rgmdatadir		%{rgm_path}/%{name}-%{version}
%define rgmlinkdir      %{rgm_path}/%{name}
# /var/lib/rgm/rgmweb
%define rgmlibdir       %{_sharedstatedir}/rgm/%{name}

%description
RGMWEB is the web frontend for the RGM appliance : %{rgm_web_site}


%prep
%setup -q

%build

%install
install -d -o root -g %{rgm_group} -m 0755 %{buildroot}%{rgmdatadir}
install -d -o root -g %{rgm_group} -m 0775 %{buildroot}%{rgmdatadir}/cache
install -d -o root -g %{rgm_group} -m 0755 %{buildroot}%{rgmlibdir}
install -d -o root -g %{rgm_group} -m 0755 %{buildroot}%{rgmlibdir}/sql
install -d -m0755 %{buildroot}%{_sysconfdir}/httpd/conf.d
#install -d -o root -g %{rgm_group} -m 0755 %{buildroot}%{rgmdocdir}
cp -afv ./* %{buildroot}%{rgmdatadir}
cp %{SOURCE1} %{buildroot}%{rgmlibdir}/sql/
cp -afpv %{SOURCE2}  %{buildroot}%{_sysconfdir}/httpd/conf.d/%{name}.conf
cp %{SOURCE3} %{buildroot}%{rgmlibdir}/sql/
#/bin/chmod -R u=rwX,go=rX %{buildroot}%{rgmdatadir}
#/bin/chmod -R g+w %{buildroot}%{rgmdatadir}/cache

# patch apache conf file with macro values
sed -i 's|/srv/rgm/rgmweb|%{rgmlinkdir}|' %{buildroot}%{_sysconfdir}/httpd/conf.d/%{name}.conf
sed -i 's|AuthrgmMySQLUsername rgminternal|AuthrgmMySQLUsername %{rgm_sql_internal_user}|' %{buildroot}%{_sysconfdir}/httpd/conf.d/%{name}.conf
sed -i 's|AuthrgmMySQLPassword 0rd0-c0m1735-b47h0n143|AuthrgmMySQLPassword %{rgm_sql_internal_pwd}|' %{buildroot}%{_sysconfdir}/httpd/conf.d/%{name}.conf


%posttrans
ln -nsf %{rgmdatadir} %{rgmlinkdir}

# set purge cron job
echo "*/5 * * * * root /usr/bin/php %{rgmlinkdir}/include/purge.php > /dev/null 2>&1" > /etc/cron.d/rgmwebpurge
/bin/chmod 0644 /etc/cron.d/rgmwebpurge

# execute SQL postinstall script
/usr/share/rgm/manage_sql.sh -d %{rgm_db_rgmweb} -s %{rgmlibdir}/sql/schema.sql -u %{rgm_sql_internal_user} -p "%{rgm_sql_internal_pwd}"
/usr/bin/php /var/lib/rgm/rgmweb/sql/change_definer.php

%preun
rm -f %{rgmlinkdir}

%clean
rm -rf %{buildroot}


%files
%defattr(0644, root, %{rgm_group}, 0755)
%{rgmdatadir}
%{rgmlibdir}
%defattr(0664, %{rgm_user_httpd}, %{rgm_group}, 0775)
%{rgmdatadir}/cache
#%{rgmdocdir}
%defattr(0644, root, root, 0755)
%{_sysconfdir}/httpd/conf.d/%{name}.conf


%changelog
* Sun Sep 29 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-24.rgm
- Change Capacity to handle influx

* Sat Sep 28 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-23.rgm
- Add license file

* Fri Sep 27 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-22.rgm
- New menu organisation

* Thu Aug 13 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-21.rgm
- remove backward compatibility for admin_bp with EON 5.1

* Thu Jun 13 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-20.rgm
- SQL schema upgrade on ol_items table

* Fri May 31 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-19.rgm
- introduce admin_distrib module

* Mon May 06 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-18.rgm
- add users group in default schema
- fix grafana admin role for admin user

* Fri May 03 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-17.rgm
- Fix menus and initial command issue.

* Wed Apr 30 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-16.rgm
- Fix dashboard regression.

* Wed Apr 25 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-15.rgm
- Add function for API.

* Wed Apr 24 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-14.rgm
- Fix favicon.

* Wed Apr 23 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-13.rgm
- Fix user trigger credential issue.

* Wed Apr 23 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-12.rgm
- Fix user creation issue.

* Thu Apr 18 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-11.rgm
- partially cleaned cacti references
- updated rgmweb DB schema to support user email
- replaced nagvis DB backend from sqlite to mysql
- add triggers on users table to keep RGM users synced with Grafana users
- symlink creation is moved from %post to %posttrans section to avoid
  symlink deletion conflicts while upgrading

* Wed Apr 13 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-10.rgm
- Move kibana in blank.

* Wed Apr 12 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-9.rgm
- Fix menus.
- Add Capacity

* Wed Apr 10 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-8.rgm
- Change default fonts.
- Change dashboard aspect
- Fix menu for RGM.
* Wed Mar 20 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-7.rgm
- fix mariadb dependency to mariadb-libs
- move RGM group creation to rgm-base package
- fix schema.sql path on post section when invoked manage_sql.sh

* Thu Mar 14 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-6.rgm
- add dependency to rgm-base package,
- modify SQL post-installation

* Wed Mar 13 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-5.rgm
- add RGM group creation, fix perms and ownership

* Tue Mar 12 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-4.rgm
- fix apache template with authrgm

* Tue Mar 12 2019 Eric Belhomme <ebelhomme@fr.scc.com> - 1.0-3.rgm
- use of rpm-macros-rgm
- add SQL schema and scripts

* Mon Mar 11 2019 Michael Aubertin <maubertin@fr.scc.com> - 1.0-1.rgm
- Fix dependance issues base on Eric suggestions.

* Wed Feb 13 2019 Michael Aubertin <michael.aubertin@gmail.com> - 1.0-0.rgm
- Fork from EyesOfNetwork.
- Remove all config parts
- Add CI/CD Webhooks and full CI

* Wed Jan 11 2017 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 5.1-0.eon
- packaged for EyesOfNetwork appliance 5.1

* Fri Apr 08 2016 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 5.0-0.eon
- packaged for EyesOfNetwork appliance 5.0

* Fri Dec 18 2015 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.2-3.eon
- highcharts ie cache false fix
- deashboard events links fix

* Tue Dec 08 2015 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.2-2.eon
- livestatus query contact filter fix
- ged query incidents filter fix

* Wed Dec 02 2015 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.2-1.eon
- highcharts instead of ezgraph added
- ldap groups added
- csv import fix
- ged refresh during edit fix 

* Tue Sep 29 2015 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.2-0.eon
- packaged for EyesOfNetwork appliance 4.2
- new search based on thruk
- mysqli php functions 

* Tue May 20 2014 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.1-2.eon
- ldap special caracters fix

* Thu May 08 2014 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.1-1.eon
- suppress ntop and shinken fix

* Mon Jan 06 2014 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.1-0.eon
- packaged for EyesOfNetwork appliance 4.1
- ldap special caracters fix
- thruk host without service search fix

* Thu Jul 18 2013 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.0-3.eon
- SetEnvIf Cookie for Location / fix 
- ldap user creation fix 
- ldap location with "'" fix 

* Thu Jun 20 2013 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.0-2.eon
- new look&feel for IE fix
- ldap alphabetical search fix
- ldap single quote fix

* Fri Jun 07 2013 Michael Aubertin <michael.aubertin@gmail.com> - 4.0-1.eon
- adding new look&feel :) From Wonderful Laurent Belgrain Design. Thank's to him.
- ldap alphabetical search fix
- ldap single quote fix

* Thu Apr 25 2013 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 4.0-0.eon
- packaged for EyesOfNetwork appliance 4.0

* Wed Mar 06 2013 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-7.eon
- admin_bp based on mysql added
- tool external autocomplete added
- tool snmp community on ip based host fix

* Wed Jan 30 2013 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-6.eon
- admin_bp added
- summary and recurring downtimes links added
- advanced eonweb search added

* Tue Jan 22 2013 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-5.eon
- capacity for nagios fix
- new thruk report link fix

* Thu Sep 06 2012 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-4.eon
- panorama link added
- event browser fix

* Thu Aug 23 2012 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-3.eon
- side menus https fix
- seconds in clock pix

* Mon Jun 18 2012 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-2.eon
- advanced notifications added
- clock in header added

* Fri Apr 06 2012 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-1.eon
- thruk reports interface link added
- event browser optimizations added
- cookie domain added
- safari fix

* Tue Mar 13 2012 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.1-0.eon
- packaged for EyesOfNetwork appliance 3.1

* Tue Feb 28 2012 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.0-2.eon
- event browser based on mysql queries fix

* Wed Nov 23 2011 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.0-1.eon
- login case fix

* Sun Apr 10 2011 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 3.0-0.eon
- packaged for EyesOfNetwork appliance 3.0
- change password page added
- mod_perl dependency added
- downtime scheduling added
- problems thruk view added
- thruk event log view added
- cacti hostname type for synhronization added
- ldap extended search fix

* Mon Mar 14 2011 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.2-3.eon
- limited user dashboard fix

* Fri Feb 18 2011 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.2-2.eon
- ged reports with sql requests
- nagios url and cgi in variable

* Sun Dec 05 2010 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.2-1.eon
- ged trigram added 
- default language fix

* Wed Jul 28 2010 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.2-0.eon
- packaged for EyesOfNetwork appliance 2.2
- setenvif instead of setenv in apache configuration added
- request services in admin_conf added
- owned and not owned filters in events added
- error messages in events when network/http problems added
- contacts notification commands creation added
- fop check installation link added
- max csv upload size 20480 fix
- users files delete fix
- contacts and contactgroups creation fix
- contacts and contactgroups delete fix
- nagiosbp links fix
- weathermap links fix
- generate report.doc for each users fix
- ldap "," in cn fix
- ldap password encryption

* Wed May 26 2010 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.1-1.eon
- hosts and templates links in nagios configuration added
- snmp v3 with "-l authpriv" added
- ged history without 2 ack time selections fix
- users and contacts mail fix
- login page ie6 fix

* Tue Feb 16 2010 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.1-0.eon
- apache setenv added
- nagios configuration reports added
- host template display for nagios to cacti importer added 
- cacti snmp community in importer added
- jquery 1.4.2 update
- users and contacts mail fix
- ged history duration fix
- autocomplete on report_fop fix 
- ged type 1 events sources fix 
- gedmysql.cfg fix 
- import to cacti space fix
- refresh with _blank links fix

* Fri Jul 17 2009 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.0-1.eon
- updates for lilac calls
- new header
- hosts,hostgroups,servicegroups autocomplete

* Fri Jul 10 2009 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 2.0-0.eon
- packaged for EyesOfNetwork appliance 2.0
- new look with navigation bar
- jquery 1.3.2
- jquery-ui 1.7.1
- lilac database rights
- updates for ged 1.2-2
- new header
- hosts,hostgroups,servicegroups autocomplete

* Mon Feb 23 2009 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 1.2-0.eon
- groups, users and ldap_users order by name
- ndo2db process management added
- ged comment with acknowledge functionality added
- ged process management fixed
- ged delete in history queue activated
- nagios hostgroups and servicesgroups links added
- nagios downtimes link renamed

* Mon Dec 22 2008 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 1.1-0.eon
- no more jpgraph welcome to eZcomponents
- french and english languages for titles
- crons for purge and backup
- new ged reports

* Mon Sep 08 2008 Jean-Philippe Levy <jeanphilippe.levy@gmail.com> - 1.0-0.eon
- packaged for EyesOfNetwork appliance
