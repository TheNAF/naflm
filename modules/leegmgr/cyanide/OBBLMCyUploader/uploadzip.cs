using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Net;
using System.IO;

namespace OBBLMCyUploader
{
    class uploadzip
    {
        private string filename;     //the full path of the zip file to upload;
        private string url;          //the url of the site to upload
        private string username;     //the username to use during authentication to retrieve a cookie
        private string password;     //the password to use during authentication to retrieve a cookie
        private string page;   //should turn into a user provided property

        private CookieContainer cookiejar = new CookieContainer();

        public string Filename
        {
            set
            {
                if (1 == 1)
                {
                    filename = value;
                }
                else
                {
                    throw new ArgumentException("The filename does not match the regular expression for a filename.");
                }
            }
        }

        public string Url
        {
            set
            {
                if (1 == 1)
                {
                    url = value;
                }
                else
                {
                    throw new ArgumentException("The url does not match the regular expression for a filename.");
                }
            }
        }

        public string Username
        {
            set{ username = value; }
        }

        public string Password
        {
            set { password = value; }
        }

        public string Page
        {
            set { page = value; }
        }

        public uploadzip()
        {

        }

        ~uploadzip()
        {

        }

        public bool start()
        {
            this.authenticate();
            this.upload();
            return true;
        }

        private void authenticate()
        {
            byte[] postData = Encoding.UTF8.GetBytes("coach=" + this.username + "&passwd=" + this.password + "&remember=1&login=Login");
            
            System.Net.ServicePointManager.Expect100Continue = false;
            WebRequest request = WebRequest.Create(this.url);
            HttpWebRequest httpreq = (HttpWebRequest)request;
            httpreq.CookieContainer = new CookieContainer();
            httpreq.Method = "POST";
            httpreq.ContentType = "application/x-www-form-urlencoded";
            httpreq.ContentLength = postData.Length;
            
            httprequest(httpreq, postData);            
            httpresponse(httpreq);                                      //since this is passed by reference by default
            this.cookiejar = httpreq.CookieContainer;                   //the cookie can be set here to be used later for the authentication of the upload
        }

        private void upload()
        {
            string boundary = "-----------------------------7da1c51404c4";  //delimiter for the postData to separate the POST fields
            byte[] zipfilecontents = this.filestream2bytearray(this.filename);  //contents up a byte array as required for POST
            byte[] postData;    //the final variable for the post data that will conbine postData1, zipfilecontents, and postData2 in that order

            byte[] postData1 =  //figured out the format using Fiddler2
                Encoding.UTF8.GetBytes(
                    boundary + Environment.NewLine +
                    "Content-Disposition: form-data; name=\"MAX_FILE_SIZE\"" + 
                    Environment.NewLine + Environment.NewLine +
                    "256000" + Environment.NewLine +
                    boundary + Environment.NewLine +
                    "Content-Disposition: form-data; name=\"userfile\"; filename=\"C:\\a.zip\"" + 
                    Environment.NewLine +
                    "Content-Type: application/x-zip-compressed" + Environment.NewLine +
                    Environment.NewLine
                );

            byte[] postData2 =
                Encoding.UTF8.GetBytes(
                    Environment.NewLine + boundary + Environment.NewLine +
                    "Content-Disposition: form-data; name=\"ffatours\"" + Environment.NewLine +
                    Environment.NewLine +
                    "1" + Environment.NewLine +
                    boundary + "--" +
                    Environment.NewLine
                );

            postData = array_byte_append(postData1, zipfilecontents);   //returns postData1 appended with zipfilecontents as a byte[]
            postData = array_byte_append(postData, postData2);          //returns postData appended with zipfilecontents as a byte[]

            System.Net.ServicePointManager.Expect100Continue = false;   //removes the "Expect: 100-Continue" http header
            WebRequest request2 = WebRequest.Create(this.url+this.page);//sends the request to the match report page
            HttpWebRequest httpreq2 = (HttpWebRequest)request2;         //casts the generic WebRequest to HttpWebRequest adding more http specific functionality
            httpreq2.CookieContainer = new CookieContainer();           //
            httpreq2.CookieContainer = this.cookiejar;                  //pulls the cookie from the authentication request prior
            httpreq2.Method = "POST";                                   //sets to a POST as default is GET
            httpreq2.UserAgent = "OBBLMCyUploader";                     //this will allow reporting on uploads via this tool
            httpreq2.ContentType = "multipart/form-data; boundary=" +   //used Fiddler2 to figure out the the boudary should be 2 dashes short here
                   boundary.Substring(2, boundary.Length - 2);          //it is full length throughout the body except at the end where it is ended with 2 dashes
            httpreq2.ContentLength = postData.Length;                   //required length of the entire data posted
            
            httprequest(httpreq2, postData);                            //makes the request
            httpresponse(httpreq2);                                     //gets the response and sets the cookie
        }

        private byte[] array_byte_append(byte[] byteArray1, byte[] byteArray2)
        {
            byte[] mergedArray = new byte[byteArray1.Length + byteArray2.Length];
            byteArray1.CopyTo(mergedArray, 0);
            int ii = 0;
            foreach (byte i in byteArray2) 
            {
                mergedArray.SetValue(i, byteArray1.Length + ii);
                ii++;
            }
            ii = 0;
            return mergedArray;
        }

        private byte[] filestream2bytearray(string file_name)
        {
            FileStream readStream = new FileStream(file_name, FileMode.Open);
            BinaryReader readBinary = new BinaryReader(readStream);
            Byte[] bytes = readBinary.ReadBytes((Int32)readStream.Length); 
            readStream.Close();
            return bytes;
        }

        private void httprequest(HttpWebRequest httpreq, byte[] byteArray)
        {
            Stream dataStream = httpreq.GetRequestStream();
            dataStream.Write(byteArray, 0, byteArray.Length);
            dataStream.Close();
        }
        
        private string httpresponse(HttpWebRequest httpreq)
        {
            HttpWebResponse response = (HttpWebResponse)httpreq.GetResponse();
            Console.WriteLine(((HttpWebResponse)response).StatusDescription);
            Stream dataStream = response.GetResponseStream();
            StreamReader reader = new StreamReader(dataStream);
            string responseFromServer = reader.ReadToEnd();
            reader.Close();
            dataStream.Close();
            response.Close();
            return responseFromServer;
        }
    }
}