<?php

namespace App\Helpers;

use TCPDF;

use TCPDF_STATIC;

use setasign\Fpdi\Tcpdf\Fpdi;

class PdfSignerv1 extends Fpdi
{
    
//below 2 function are custmized for set signature in document and Output pdf
//As this is updated version of TCPDF, i haved copied my 2 function from old folder file to this file
//on 20-01-2020 by Amol	

    public function my_set_sign($signing_cert = '', $private_key = '', $private_key_password = '', $extracerts = '', $cert_type = 2, $info = array(), $approval = '') {

        $this->sign = true;
        ++$this->n;
        $this->sig_obj_id = $this->n; // signature widget
        ++$this->n; // signature object ($this->sig_obj_id + 1)
        $this->signature_data = array();
        /* 	if (strlen($signing_cert) == 0) {
          $this->Error('Please provide a certificate file and password!');
          }
          if (strlen($private_key) == 0) {
          $private_key = $signing_cert;
          } */
        $this->signature_data['signcert'] = $signing_cert;
        $this->signature_data['privkey'] = $private_key;
        $this->signature_data['password'] = $private_key_password;
        $this->signature_data['extracerts'] = $extracerts;
        $this->signature_data['cert_type'] = $cert_type;
        $this->signature_data['info'] = $info;
        $this->signature_data['approval'] = $approval;
    }

    public function my_output($name = 'doc.pdf', $dest = 'I', $pdf_file = null, $cer_value = null, $pkcs7_value = null, $signing = false,$pdf_byte_range=null) {

        //Output PDF to some destination
        //Finish document if necessary
        if ($this->state < 3) {
            $this->Close();
        }
        //Normalize parameters
        if (is_bool($dest)) {
            $dest = $dest ? 'D' : 'F';
        }
        $dest = strtoupper($dest);
        if ($dest[0] != 'F') {
            $name = preg_replace('/[\s]+/', '_', $name);
            $name = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $name);
        }

        if ($signing == true) {
            $this->sign = true;
        }


        if ($this->sign) {
            // *** apply digital signature to the document ***
            // get the document content

            if ($pdf_file != null) {
                //$pdfdoc = file_get_contents($pdf_file);
                $pdfdoc = $pdf_file;
            } else {
                $pdfdoc = $this->getBuffer();

                // remove last newline
                $pdfdoc = substr($pdfdoc, 0, -1);
                // remove filler space
                $byterange_string_len = strlen(TCPDF_STATIC::$byterange_string);
                // define the ByteRange
                $byte_range = array();
                $byte_range[0] = 0;
                $byte_range[1] = strpos($pdfdoc, TCPDF_STATIC::$byterange_string) + $byterange_string_len + 10;
                $this->pdf_byte_range = $byte_range[1];
                $byte_range[2] = $byte_range[1] + $this->signature_max_length + 2;

                $byte_range[3] = strlen($pdfdoc) - $byte_range[2];
                //
                $pdfdoc = substr($pdfdoc, 0, $byte_range[1]) . substr($pdfdoc, $byte_range[2]);

                // replace the ByteRange
                $byterange = sprintf('/ByteRange[0 %u %u %u]', $byte_range[1], $byte_range[2], $byte_range[3]);
                $byterange .= str_repeat(' ', ($byterange_string_len - strlen($byterange)));
                $pdfdoc = str_replace(TCPDF_STATIC::$byterange_string, $byterange, $pdfdoc);
                // write the document to a temporary folder
                $tempdoc = TCPDF_STATIC::getObjFilename('doc', $this->file_id);
                $f = TCPDF_STATIC::fopenLocal($tempdoc, 'wb');
                if (!$f) {
                    $this->Error('Unable to create temporary file: ' . $tempdoc);
                }
                $pdfdoc_length = strlen($pdfdoc);
                fwrite($f, $pdfdoc, $pdfdoc_length);
                fclose($f);
                // get digital signature via openssl library
                $tempsign = TCPDF_STATIC::getObjFilename('sig', $this->file_id);
                /* 		if (empty($this->signature_data['extracerts'])) {
                  openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data['signcert'], array($this->signature_data['privkey'], $this->signature_data['password']), array(), PKCS7_BINARY | PKCS7_DETACHED);
                  } else {
                  openssl_pkcs7_sign($tempdoc, $tempsign, $this->signature_data['signcert'], array($this->signature_data['privkey'], $this->signature_data['password']), array(), PKCS7_BINARY | PKCS7_DETACHED, $this->signature_data['extracerts']);
                  }
                 */
                // read signature
                $signature = file_get_contents($tempsign);

                // extract signature
                /* 	$signature = substr($signature, $pdfdoc_length);

                  $signature = substr($signature, (strpos($signature, "%%EOF\n\n------") + 13));

                  $tmparr = explode("\n\n", $signature);
                  $signature = $tmparr[1];
                 */
            }

            if ($cer_value != null) {
                // decode signature
                $signature = base64_decode(trim($pkcs7_value));
                //print_r($_SESSION['pdf_byte_range_1']);exit;
                // add TSA timestamp to signature
                $signature = $this->applyTSA($signature);

                // convert signature to hex
                $signature = current(unpack('H*', $signature));

                $signature = str_pad($signature, $this->signature_max_length, '0');
                // Add signature to the document Final
                $this->buffer = substr($pdfdoc, 0, $pdf_byte_range) . '<' . $signature . '>' . substr($pdfdoc, $pdf_byte_range);
                 
            } else {

                // Add signature to the document
                $this->buffer = substr($pdfdoc, 0, $byte_range[1]) . substr($pdfdoc, $byte_range[1]);
            }


            //print_r($this->buffer);exit();
            $this->bufferlen = strlen($this->buffer);
        }
        
        switch ($dest) {
            case 'I': {
                    // Send PDF to the standard output
                    if (ob_get_contents()) {
                        $this->Error('Some data has already been output, can\'t send PDF file');
                    }
                    if (php_sapi_name() != 'cli') {
                        // send output to a browser
                        header('Content-Type: application/pdf');
                        if (headers_sent()) {
                            $this->Error('Some data has already been output to browser, can\'t send PDF file');
                        }
                        header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                        //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                        header('Pragma: public');
                        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                        header('Content-Disposition: inline; filename="' . basename($name) . '"');
                        TCPDF_STATIC::sendOutputData($this->getBuffer(), $this->bufferlen);
                    } else {
                        echo $this->getBuffer();
                    }
                    break;
                }
            case 'D': {
                    // download PDF as file
                    if (ob_get_contents()) {
                        $this->Error('Some data has already been output, can\'t send PDF file');
                    }
                    header('Content-Description: File Transfer');
                    if (headers_sent()) {
                        $this->Error('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                    // force download dialog
                    if (strpos(php_sapi_name(), 'cgi') === false) {
                        header('Content-Type: application/force-download');
                        header('Content-Type: application/octet-stream', false);
                        header('Content-Type: application/download', false);
                        header('Content-Type: application/pdf', false);
                    } else {
                        header('Content-Type: application/pdf');
                    }
                    // use the Content-Disposition header to supply a recommended filename
                    header('Content-Disposition: attachment; filename="' . basename($name) . '"');
                    header('Content-Transfer-Encoding: binary');
                    TCPDF_STATIC::sendOutputData($this->getBuffer(), $this->bufferlen);
                    break;
                }
            case 'F':
            case 'FI':
            case 'FD': {
                    // save PDF to a local file
                    $f = TCPDF_STATIC::fopenLocal($name, 'wb');
                    if (!$f) {
                        $this->Error('Unable to create output file: ' . $name);
                    }
                    fwrite($f, $this->getBuffer(), $this->bufferlen);
                    fclose($f);
                    if ($dest == 'FI') {
                        // send headers to browser
                        header('Content-Type: application/pdf');
                        header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                        //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                        header('Pragma: public');
                        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                        header('Content-Disposition: inline; filename="' . basename($name) . '"');
                        TCPDF_STATIC::sendOutputData(file_get_contents($name), filesize($name));
                    } elseif ($dest == 'FD') {
                        // send headers to browser
                        if (ob_get_contents()) {
                            $this->Error('Some data has already been output, can\'t send PDF file');
                        }
                        header('Content-Description: File Transfer');
                        if (headers_sent()) {
                            $this->Error('Some data has already been output to browser, can\'t send PDF file');
                        }
                        header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                        header('Pragma: public');
                        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                        // force download dialog
                        if (strpos(php_sapi_name(), 'cgi') === false) {
                            header('Content-Type: application/force-download');
                            header('Content-Type: application/octet-stream', false);
                            header('Content-Type: application/download', false);
                            header('Content-Type: application/pdf', false);
                        } else {
                            header('Content-Type: application/pdf');
                        }
                        // use the Content-Disposition header to supply a recommended filename
                        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
                        header('Content-Transfer-Encoding: binary');
                        TCPDF_STATIC::sendOutputData(file_get_contents($name), filesize($name));
                    }
                    break;
                }
            case 'E': {
                    // return PDF as base64 mime multi-part email attachment (RFC 2045)
                    $retval = 'Content-Type: application/pdf;' . "\r\n";
                    $retval .= ' name="' . $name . '"' . "\r\n";
                    $retval .= 'Content-Transfer-Encoding: base64' . "\r\n";
                    $retval .= 'Content-Disposition: attachment;' . "\r\n";
                    $retval .= ' filename="' . $name . '"' . "\r\n\r\n";
                    $retval .= chunk_split(base64_encode($this->getBuffer()), 76, "\r\n");
                    return $retval;
                }
            case 'S': {
                    // returns PDF as a string
                    return $this->getBuffer();
                }
            default: {
                    $this->Error('Incorrect output destination: ' . $dest);
                }
        }
        return '';
    }
	    
    // public function Footer()
    // {
    //     $this->SetY(-15);
    //     $this->SetFont('helvetica', 'B', 8);
    //     $this->Cell(0, 10, 'Generated by: ONTRACK '  . "\n" . 'Date: ' . date('d-m-Y') . "\n" . date('h:i a'), 0, false, 'C', 0, '', 0, false, 'T', 'M');

    //     // OR image signature
    //     // $this->Image('signature.png', 80, $this->getPageHeight() - 25, 50);
    // }
}