using System;
using System.IO;
using System.Net;
using System.Text;

namespace OBBLMCyUploader
{
    class OBBLMCyUploader_cls
    {
        private static FileSystemEventArgs newfile;
        private static string url;
        private static string page = "handler.php?type=leegmgr";
        private static string username;
        private static string password;
        private static Boolean debug = true;

        static void Main(string[] args)
        {
            System.Console.WriteLine("The program has started.");
            if (args.Length == 3)
            {
                url = args[0];
                username = args[1];
                password = args[2];
            }
            else if (debug == false)
            {
                url = "http://www.stuntyleeg.com/";
                username = "testusername";
                password = "testpassword";
                uploadzip uploadZip1 = new uploadzip();
                uploadZip1.Filename = "C:\\Documents and Settings\\username\\My Documents\\BloodBowl\\Match_2010-07-13_20-12-45.zip";
                uploadZip1.Password = password;
                uploadZip1.Url = url;
                uploadZip1.Username = username;
                uploadZip1.Page = page;
                uploadZip1.start();
                Console.ReadKey(true);
                Environment.Exit(1);
            }
            else
            {
                System.Console.WriteLine("This program accepts 3 arguments: url, username, and password.");
                System.Console.WriteLine("Please use the following example and follow it as closely as possible.");
                System.Console.WriteLine("OBBLMCyUploader.exe http://www.example.com/ \"user name\" \"this ismy_password\"");
                Console.WriteLine("Press any key to continue...");
                Console.ReadKey(true);
                Environment.Exit(1);
            }

            string mydocs = Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments);
            string bbdir = (Directory.Exists(mydocs + "\\BloodBowlLegendary\\")) ? mydocs + "\\BloodBowlLegendary\\" : mydocs + "\\BloodBowl\\";
            string matchreport = bbdir;
            string replay = bbdir + "Saves\\Replays\\";
            string zipfile = bbdir;
            
            FileSystemWatcher sw_replay = new FileSystemWatcher();
            Console.WriteLine("The replay folder is being monitored.");
            sw_replay.Path = replay;
            sw_replay.Filter = "Replay_*.db";
            sw_replay.Created += new FileSystemEventHandler(OnChanged);
            sw_replay.EnableRaisingEvents = true;

            while (newfile == null)
            {

            }

            sw_replay.EnableRaisingEvents = false;
            
            replay = newfile.FullPath;
            zipfile += "Match_" + newfile.Name.Substring(7,newfile.Name.Length-7-3)+".zip";//-7 for Replay_ and -3 for .db
            //Replay_2010-07-03_17-47-21.db
            newfile = null;

            FileSystemWatcher sw_matchreport = new FileSystemWatcher();
            System.Console.WriteLine("The match report folder is being monitored.");
            sw_matchreport.Path = matchreport;
            sw_matchreport.Filter = "MatchReport.sqlite";
            sw_matchreport.Changed += new FileSystemEventHandler(OnChanged);
            sw_matchreport.EnableRaisingEvents = true;

            while (newfile == null)
            {

            }

            sw_matchreport.EnableRaisingEvents = false;

            matchreport += "MatchReport.sqlite";

            Shell32.ShellClass sc = new Shell32.ShellClass();
            if (createzip(zipfile))
            {
                Shell32.Folder DestFlder = sc.NameSpace(zipfile);
                DestFlder.CopyHere(matchreport);
                checkStatus(1, DestFlder);
                DestFlder.CopyHere(replay);
                checkStatus(2, DestFlder);
            }
            
            uploadzip uploadZip = new uploadzip();
            uploadZip.Filename = zipfile;
            uploadZip.Password = password;
            uploadZip.Url = url;
            uploadZip.Page = page;
            uploadZip.Username = username;
            uploadZip.start();

            Console.WriteLine("The program has finished.");
            Console.WriteLine("Press any key to continue...");
            Console.ReadKey(true);
        }

        // Define the event handlers.
        private static void OnChanged(object source, FileSystemEventArgs e)
        {
            // Specify what is done when a file is changed, created, or deleted.
            Console.WriteLine("File: " + e.FullPath + " " + e.ChangeType);
            newfile = e;
        }

        private static Boolean checkStatus(int ItemCount, Shell32.Folder DestFlder)
        {
            DateTime timeoutDeadline = DateTime.Now.AddMinutes(1);

            for (; ; )
            {
                //Are we past the deadline?
                if (DateTime.Now > timeoutDeadline)
                {
                    break;
                }

                //Check the number of items in the new zip to see if it matches
                //the number of items in the original source location

                //Only check the item count every .25 seconds
                System.Threading.Thread.Sleep(250);

                int ZipFileItemCount = RecurseCount(DestFlder.Items());

                if (ItemCount == ZipFileItemCount)
                {
                    break;
                }
            }
            return true;
        }
 
        private static int RecurseCount(Shell32.FolderItems Source)
        {
            int ItemCount = 0;

            foreach (Shell32.FolderItem item in Source)
            {
                if (item.IsFolder == true)
                {
                    //Add one for this folder
                    ItemCount++;
                    //Then continue walking down the folder tree
                    ItemCount += RecurseCount(((Shell32.Folder)item.GetFolder).Items());
                }
                else
                {
                    //Add one for this file
                    ItemCount++;
                }
            }

            return ItemCount;
        }

        private static bool createzip(string zipname)
        {
            //Create an empty zip file
            byte[] emptyzip = new byte[] { 80, 75, 5, 6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 };

            FileStream fs = File.Create(zipname);
            fs.Write(emptyzip, 0, emptyzip.Length);
            fs.Flush();
            fs.Close();
            fs = null;
            return true;
        }
    }
}