<?php
require_once 'auth-check.php';
checkAuth();

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="timetable_template.xls"');

echo '<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection ss:Protected="0"/>
  </Style>
  <Style ss:ID="sHeader">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFFFFF" ss:Bold="1"/>
   <Interior ss:Color="#1e40af" ss:Pattern="Solid"/>
   <Protection ss:Protected="1"/>
  </Style>
  <Style ss:ID="sLocked">
   <Interior ss:Color="#FFFBEB" ss:Pattern="Solid"/> <!-- Light Yellow -->
   <Protection ss:Protected="1"/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#FFA500" ss:Bold="1"/> <!-- Orange text for Break -->
  </Style>
  <Style ss:ID="sTime">
    <NumberFormat ss:Format="@"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Timetable">
  <Table ss:ExpandedColumnCount="7" x:FullColumns="1" x:FullRows="1" ss:DefaultColumnWidth="60">
   <Column ss:Width="100"/>
   <Column ss:Width="80"/>
   <Column ss:StyleID="sTime" ss:Width="80"/>
   <Column ss:StyleID="sTime" ss:Width="80"/>
   <Column ss:Width="150"/>
   <Column ss:Width="150"/>
   <Column ss:Width="80"/>
   <Row>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Class Name</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Day</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Start Time (HH:MM)</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">End Time (HH:MM)</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Subject</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Teacher Email</Data></Cell>
    <Cell ss:StyleID="sHeader"><Data ss:Type="String">Room</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">JSS 1A</Data></Cell>
    <Cell><Data ss:Type="String">Monday</Data></Cell>
    <Cell><Data ss:Type="String">08:00</Data></Cell>
    <Cell><Data ss:Type="String">08:40</Data></Cell>
    <Cell><Data ss:Type="String">Mathematics</Data></Cell>
    <Cell><Data ss:Type="String">teacher@example.com</Data></Cell>
    <Cell><Data ss:Type="String">Room 1A</Data></Cell>
   </Row>
   <Row>
    <Cell><Data ss:Type="String">JSS 1A</Data></Cell>
    <Cell><Data ss:Type="String">Monday</Data></Cell>
    <Cell><Data ss:Type="String">08:40</Data></Cell>
    <Cell><Data ss:Type="String">09:20</Data></Cell>
    <Cell><Data ss:Type="String">English Language</Data></Cell>
    <Cell><Data ss:Type="String">teacher2@example.com</Data></Cell>
    <Cell><Data ss:Type="String">Room 1A</Data></Cell>
   </Row>
   <Row>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String">JSS 1A</Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String">Monday</Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String">09:20</Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String">09:40</Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String">Break Time</Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String">DO NOT EDIT</Data></Cell>
    <Cell ss:StyleID="sLocked"><Data ss:Type="String">DO NOT EDIT</Data></Cell>
   </Row>
      <Row>
    <Cell><Data ss:Type="String">JSS 1A</Data></Cell>
    <Cell><Data ss:Type="String">Monday</Data></Cell>
    <Cell><Data ss:Type="String">09:40</Data></Cell>
    <Cell><Data ss:Type="String">10:20</Data></Cell>
    <Cell><Data ss:Type="String">Basic Science</Data></Cell>
    <Cell><Data ss:Type="String">teacher3@example.com</Data></Cell>
    <Cell><Data ss:Type="String">Room 1A</Data></Cell>
   </Row>
  </Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
   <ProtectObjects>True</ProtectObjects>
   <ProtectScenarios>True</ProtectScenarios>
  </WorksheetOptions>
 </Worksheet>
</Workbook>';
?>